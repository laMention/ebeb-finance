<?php

namespace App\Services;

use App\Models\CompteMobileMoney;
use App\Models\ObjectifEpargne;
use App\Models\Operation;
use App\Models\PaiementEntrant;
use App\Models\QrcodePaiement;
use App\Models\ReglePrelevement;
use App\Models\TypeCotisation;
use App\Models\User;
use App\Services\AlerteGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaiementService
{
    public function __construct(
        private CotisationService   $cotisationService,
        private PortefeuilleService $portefeuilleService,
        private NotificationService $notificationService
    ) {}

    /**
     * Traite un paiement entrant dans une transaction atomique.
     * Étapes : identification → création PaiementEntrant → calcul répartition → distribution → mise à jour portefeuille.
     */
    public function traiterPaiement(array $data): array
    {
        DB::beginTransaction();
        $paiement = null;
        try {
            // [$user, $compteMobileMoney, ] = $this->identifierUtilisateur($data);
            // Verifier la reference externe de la transaction
            if(PaiementEntrant::where('reference_externe', $data['reference_externe'])->exists() ){
                return [
                    'success'    => false,
                    'message'    => 'Une transaction ayant cette référence existe déjà.'
                ];
            }

            [$user, $compteMobileMoney, $operateurSource] = $this->identifierUtilisateur($data);

            // Vérifier que le service opérateur est activé dans les paramètres globaux
            $serviceKey = $this->resoudreServiceOperateur($operateurSource ?? '');
            if ($serviceKey && !ParametreGlobalService::estActif($serviceKey)) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => "Le service {$operateurSource} est temporairement désactivé.",
                ];
            }


            $paiement = PaiementEntrant::create([
                'user_id'                => $user->id,
                'compte_mobile_money_id' => $compteMobileMoney->id,
                'montant_brut'           => $data['montant_brut'],
                'statut'                 => 'EN_ATTENTE',
                'reference_externe'      => $data['reference_externe'],
                'operateur_source'       => $operateurSource,
                'qr_code_ref'            => $data['qr_code_ref'] ?? null,
                'description'            => $data['description'] ?? null,
            ]);

            $config      = $this->chargerConfig($user);
            $montantBrut = (float) $data['montant_brut'];
            $repartition = $this->calculerRepartition($montantBrut, $config);

            // Opération principale (racine de toutes les sous-opérations)
            $opPrincipale = Operation::create([
                'user_id'             => $user->id,
                'paiement_entrant_id' => $paiement->id,
                'montant'             => $montantBrut,
                'type_operation'      => 'PAIEMENT_CLIENT',
                'statut'              => 'SUCCES',
                'reference'           => $this->genererReference('PAY'),
                'date_operation'      => now(),
                'description'         => "Paiement client — {$operateurSource}",
                'libelle'             => 'Paiement entrant',
            ]);

            $this->distribuerFonds($user, $paiement, $opPrincipale, $repartition, $config);
            $this->portefeuilleService->mettreAjourPortefeuille($user, $repartition);

            $paiement->update(['statut' => 'SUCCES']);

            DB::commit();

            // Notifications envoyées APRÈS le commit — un échec ici ne remet pas en cause la transaction
            $this->notifierEvenementsFinanciers($user, $repartition);

            return [
                'success'    => true,
                'paiement'   => $paiement->fresh()->load(['operation.sous_operations', 'compte_mobile_money']),
                'repartition'=> $this->formaterRepartition($repartition),
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            if ($paiement?->exists) {
                $paiement->update(['statut' => 'ECHEC']);
            }
            throw $e;
        }
    }

    /**
     * Récupère les paiements d'un utilisateur avec pagination.
     */
    public function listerPaiements(User $user, int $perPage = 15): \Illuminate\Pagination\LengthAwarePaginator
    {
        return PaiementEntrant::where('user_id', $user->id)
            ->with(['operation.sous_operations.type_cotisation', 'compte_mobile_money'])
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Méthodes privées
    // ─────────────────────────────────────────────────────────────────────────

    private function identifierUtilisateur(array $data): array
    {
        if (!empty($data['qr_code_ref'])) {
            $qrcode = QrcodePaiement::where('reference', $data['qr_code_ref'])
                ->where('est_actif', true)
                ->with('compte_mobile_money.user')
                ->firstOrFail();

            // Extraire l'opérateur depuis le JSON du QR code
            $valeurQr = json_decode($qrcode->valeur, true);
            $data['operateur_source'] = $data['operateur_source'] 
                                        ?? $valeurQr['op'] 
                                        ?? mettre_en_majuscule('Opérateur inconnu');

            $data['compte_mobile_money_id'] = $data['compte_mobile_money_id'] 
                                            ?? $valeurQr['compte'] 
                                            ?? mettre_en_majuscule('Compte inconnu');

            return [
                $qrcode->compte_mobile_money->user, 
                $qrcode->compte_mobile_money,
                $data['operateur_source'],
                $data['compte_mobile_money_id']
            ];
        }

        // Fallback direct par compte_mobile_money_id (ex: intégration API opérateur)
        $compte = CompteMobileMoney::with('user')->findOrFail($data['compte_mobile_money_id']);
        return [$compte->user, $compte];
    }

    private function chargerConfig(User $user): array
    {
        return [
            'objectif_epargne'    => ObjectifEpargne::where('user_id', $user->id)
                ->where('est_actif', true)
                ->first(),
            'regles_prelevements' => ReglePrelevement::where('user_id', $user->id)
                ->where('est_actif', true)
                ->with('type_cotisation')
                ->orderBy('ordre_priorite')
                ->get(),
            'declaration_revenu'  => $user->declarationRevenu,
            'taux_commission'     => (float) ParametreGlobalService::get('TAUX_COMMISSION', '3.0'),
        ];
    }

    private function calculerRepartition(float $montantBrut, array $config): array
    {
        // ── Épargne ──────────────────────────────────────────────────────────
        $montantEpargne  = 0.0;
        $objectifEpargne = $config['objectif_epargne'];

        if ($objectifEpargne?->type_calcul && $objectifEpargne?->valeur) {
            $candidat = $objectifEpargne->type_calcul === 'FIXE'
                ? (float) $objectifEpargne->valeur
                : round($montantBrut * ((float) $objectifEpargne->valeur / 100), 2);

            // Ne pas dépasser le montant restant à épargner
            $resteAEpargner = max(0.0, (float) $objectifEpargne->montant_cible - (float) $objectifEpargne->montant_epargne);
            $montantEpargne = min($candidat, $resteAEpargner);
        }

        // ── Commission plateforme ─────────────────────────────────────────────
        $commission = round($montantBrut * ($config['taux_commission'] / 100), 2);

        // ── Cotisations (par ordre de priorité) ──────────────────────────────
        $cotisations = [];
        foreach ($config['regles_prelevements'] as $regle) {
            $montantCot = $regle->type_calcul === 'FIXE'
                ? (float) $regle->valeur
                : round($montantBrut * ((float) $regle->valeur / 100), 2);

            $cotisations[] = [
                'type_cotisation'    => $regle->type_cotisation,
                'type_cotisation_id' => $regle->type_cotisation_id,
                'montant'            => $montantCot,
            ];
        }

        $totalCotisations = (float) array_sum(array_column($cotisations, 'montant'));
        $montantNet       = max(0.0, $montantBrut - $montantEpargne - $totalCotisations - $commission);

        return [
            'montant_brut'      => $montantBrut,
            'epargne'           => $montantEpargne,
            'objectif_epargne'  => $objectifEpargne,
            'cotisations'       => $cotisations,
            'total_cotisations' => $totalCotisations,
            'commission'        => $commission,
            'montant_net'       => $montantNet,
        ];
    }

    private function distribuerFonds(
        User $user,
        PaiementEntrant $paiement,
        Operation $opPrincipale,
        array $repartition,
        array $config
    ): void {
        // ── Épargne ──────────────────────────────────────────────────────────
        if ($repartition['epargne'] > 0 && $repartition['objectif_epargne']) {
            $objectif = $repartition['objectif_epargne'];

            Operation::create([
                'user_id'             => $user->id,
                'paiement_entrant_id' => $paiement->id,
                'objectif_epargne_id' => $objectif->id,
                'montant'             => $repartition['epargne'],
                'type_operation'      => 'EPARGNE',
                'statut'              => 'SUCCES',
                'reference'           => $this->genererReference('EPG'),
                'date_operation'      => now(),
                'operation_parent_id' => $opPrincipale->id,
                'description'         => "Prélèvement épargne — {$objectif->libelle}",
                'libelle'             => 'Épargne automatique',
            ]);

            $nouveauMontant = (float) $objectif->montant_epargne + $repartition['epargne'];
            $miseAJour = ['montant_epargne' => $nouveauMontant];

            // Suspension automatique si objectif atteint
            if ($nouveauMontant >= (float) $objectif->montant_cible) {
                $miseAJour['est_actif'] = false;
            }
            $objectif->update($miseAJour);
        }

        // ── Cotisations ───────────────────────────────────────────────────────
        foreach ($repartition['cotisations'] as $cotData) {
            $typeCotisation = $cotData['type_cotisation'];
            $typeOp         = $this->resoudreTypeOperation($typeCotisation);

            $opCotisation = Operation::create([
                'user_id'             => $user->id,
                'paiement_entrant_id' => $paiement->id,
                'type_cotisation_id'  => $cotData['type_cotisation_id'],
                'montant'             => $cotData['montant'],
                'type_operation'      => $typeOp,
                'statut'              => 'SUCCES',
                'reference'           => $this->genererReference('COT'),
                'date_operation'      => now(),
                'operation_parent_id' => $opPrincipale->id,
                'description'         => "Cotisation {$typeCotisation->libelle}",
                'libelle'             => $typeCotisation->libelle,
            ]);

            $this->cotisationService->enregistrerVersement(
                $user,
                $cotData['type_cotisation_id'],
                $typeCotisation,
                $cotData['montant'],
                $config['declaration_revenu'],
                $paiement->id,
                $opPrincipale->id
            );
        }

        // ── Commission ────────────────────────────────────────────────────────
        if ($repartition['commission'] > 0) {
            Operation::create([
                'user_id'             => $user->id,
                'paiement_entrant_id' => $paiement->id,
                'montant'             => $repartition['commission'],
                'type_operation'      => 'COMMISSION_PLATEFORME',
                'statut'              => 'SUCCES',
                'reference'           => $this->genererReference('COM'),
                'date_operation'      => now(),
                'operation_parent_id' => $opPrincipale->id,
                'description'         => 'Commission plateforme Ebeb Finance',
                'libelle'             => 'Commission',
            ]);
        }
    }

    private function resoudreServiceOperateur(string $operateur): ?string
    {
        $map = [
            'WAVE'         => 'SERVICE_WAVE',
            'ORANGE_MONEY' => 'SERVICE_ORANGE_MONEY',
            'ORANGE'       => 'SERVICE_ORANGE_MONEY',
            'MTN'          => 'SERVICE_MTN',
            'MTN_MONEY'    => 'SERVICE_MTN',
            'MOOV'         => 'SERVICE_MOOV',
            'MOOV_MONEY'   => 'SERVICE_MOOV',
        ];
        return $map[strtoupper($operateur)] ?? null;
    }

    private function resoudreTypeOperation(TypeCotisation $type): string
    {
        $code      = strtoupper($type->code ?? '');
        $categorie = strtoupper($type->categorie ?? '');

        if (str_contains($code, 'CNPS') || str_contains($categorie, 'CNPS')) return 'COTISATION_CNPS';
        if (str_contains($code, 'AMU')  || str_contains($categorie, 'AMU'))  return 'COTISATION_AMU';
        if (str_contains($categorie, 'ASSURANCE')) return 'ASSURANCE_PERSONNALISEE';

        return 'COTISATION_PERSONNALISEE';
    }

    private function genererReference(string $prefix): string
    {
        return $prefix . '-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(6));
    }

    /**
     * Envoie toutes les notifications liées à un paiement traité.
     * Appelée APRÈS DB::commit() — un échec ici ne remet pas en cause la transaction.
     */
    private function notifierEvenementsFinanciers(User $user, array $repartition): void
    {
        try {
            // 1. Paiement reçu
            $this->notificationService->notifierPaiementRecu($user, $repartition['montant_brut']);

            // 2. Épargne
            if ($repartition['epargne'] > 0 && $repartition['objectif_epargne']) {
                $objectif = $repartition['objectif_epargne'];

                $this->notificationService->notifierDeductionEpargne(
                    $user, $repartition['epargne'], $objectif->libelle
                );

                // Objectif d'épargne atteint ?
                $totalEpargne = (float) $objectif->montant_epargne + $repartition['epargne'];
                $objectifAtteint = $totalEpargne >= (float) $objectif->montant_cible;
                if ($objectifAtteint) {
                    $this->notificationService->notifierObjectifEpargneAtteint($user, $objectif->libelle);
                }

                AlerteGenerator::transaction(
                    $objectifAtteint ? 'SUCCES' : 'INFO',
                    $objectifAtteint
                        ? "Objectif d'épargne atteint — {$objectif->libelle}"
                        : "Prélèvement épargne automatique — {$objectif->libelle}",
                    "{$user->prenom} {$user->nom} : " . number_format($repartition['epargne'], 0, ',', ' ') . " FCFA prélevé(s) pour l'épargne « {$objectif->libelle} »."
                        . ($objectifAtteint ? " Objectif de " . number_format((float) $objectif->montant_cible, 0, ',', ' ') . " FCFA atteint." : ''),
                );
            }

            // 3. Cotisations et assurances
            foreach ($repartition['cotisations'] as $cot) {
                $type      = $cot['type_cotisation'];
                $categorie = strtoupper($type->categorie ?? '');

                str_contains($categorie, 'ASSURANCE')
                    ? $this->notificationService->notifierDeductionAssurance($user, $cot['montant'], $type->libelle)
                    : $this->notificationService->notifierDeductionCotisation($user, $cot['montant'], $type->libelle);

                AlerteGenerator::transaction(
                    'INFO',
                    "Prélèvement cotisation — {$type->libelle}",
                    "{$user->prenom} {$user->nom} : " . number_format($cot['montant'], 0, ',', ' ') . " FCFA prélevé(s) pour la cotisation « {$type->libelle} ».",
                );
            }

            // 4. Commission
            if ($repartition['commission'] > 0) {
                $this->notificationService->notifierCommission($user, $repartition['commission']);
            }

        } catch (\Exception $e) {
            \Log::warning('Échec des notifications financières (transaction déjà committée)', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    private function formaterRepartition(array $repartition): array
    {
        return [
            'montant_brut'      => $repartition['montant_brut'],
            'epargne'           => $repartition['epargne'],
            'total_cotisations' => $repartition['total_cotisations'],
            'commission'        => $repartition['commission'],
            'montant_net'       => $repartition['montant_net'],
            'cotisations'       => array_map(fn($c) => [
                'libelle' => $c['type_cotisation']->libelle,
                'montant' => $c['montant'],
            ], $repartition['cotisations']),
        ];
    }
}
