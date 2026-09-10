<?php

namespace App\Services;

use App\Models\Administrateur;
use App\Models\Operation;
use App\Models\Remboursement;
use App\Models\ReglePrelevement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Remboursement, par un administrateur, d'un prélèvement de cotisation
 * erroné — outil de correction pour les montants prélevés à tort (ex. un
 * type de cotisation jamais configuré par l'utilisateur, voir
 * `PaiementService::calculerRepartition()`).
 */
class RemboursementService
{
    public function __construct(
        private CotisationService   $cotisationService,
        private NotificationService $notificationService,
    ) {}

    /**
     * Contrôles avant remboursement (règles 1, 2, 3, 5). Ne bloque
     * jamais sur la règle 5 (configuration au moment du prélèvement) :
     * aucun historique versionné de `regle_prelevements` n'existe dans ce
     * projet, la vérification ne peut donc porter que sur l'existence
     * actuelle/passée d'une règle — un simple avertissement, l'administrateur
     * restant juge final via le motif obligatoire et la confirmation (règle 8).
     */
    public function verifierEligibilite(Operation $operation): array
    {
        $avertissements = [];

        if ($operation->statut !== 'SUCCES') {
            return [
                'eligible'       => false,
                'raison'         => "Seul un prélèvement effectivement réalisé (statut SUCCES) peut être remboursé.",
                'avertissements' => [],
                'montant_max'    => 0,
            ];
        }

        if (!$operation->type_cotisation_id) {
            return [
                'eligible'       => false,
                'raison'         => "Cette opération n'est pas rattachée à une cotisation.",
                'avertissements' => [],
                'montant_max'    => 0,
            ];
        }

        if (Remboursement::where('operation_id', $operation->id)->exists()) {
            return [
                'eligible'       => false,
                'raison'         => 'Ce prélèvement a déjà été remboursé.',
                'avertissements' => [],
                'montant_max'    => 0,
            ];
        }

        $regleExiste = ReglePrelevement::withTrashed()
            ->where('user_id', $operation->user_id)
            ->where('type_cotisation_id', $operation->type_cotisation_id)
            ->exists();

        if ($regleExiste) {
            $avertissements[] = "Une règle de prélèvement existe (ou a existé) pour ce type de cotisation — vérifier qu'elle n'était pas active au moment du prélèvement avant de confirmer.";
        }

        return [
            'eligible'       => true,
            'raison'         => null,
            'avertissements' => $avertissements,
            'montant_max'    => (float) $operation->montant,
        ];
    }

    /**
     * @throws \RuntimeException si l'opération n'est pas éligible ou si le
     *         montant demandé est invalide — messages destinés à être
     *         renvoyés tels quels par le contrôleur.
     */
    public function rembourser(
        Operation $operation,
        Administrateur $admin,
        string $motif,
        ?float $montant = null,
    ): Remboursement {
        // Re-vérification dans la transaction : protège contre une exécution
        // concurrente entre l'affichage du récapitulatif et la confirmation.
        $eligibilite = $this->verifierEligibilite($operation);
        if (!$eligibilite['eligible']) {
            throw new \RuntimeException($eligibilite['raison']);
        }

        $montant ??= (float) $operation->montant;
        if ($montant <= 0) {
            throw new \RuntimeException('Le montant à rembourser doit être supérieur à 0.');
        }
        if (bccomp((string) $montant, (string) $operation->montant, 2) > 0) {
            throw new \RuntimeException('Le montant à rembourser ne peut pas dépasser le montant prélevé.');
        }

        return DB::transaction(function () use ($operation, $admin, $motif, $montant) {
            $typeCotisation = $operation->type_cotisation;

            $operationRemboursement = Operation::create([
                'user_id'             => $operation->user_id,
                'type_cotisation_id'  => $operation->type_cotisation_id,
                'paiement_entrant_id' => $operation->paiement_entrant_id,
                'montant'             => $montant,
                'type_operation'      => 'REMBOURSEMENT_COTISATION',
                'statut'              => 'SUCCES',
                'reference'           => 'RMB-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(6)),
                'date_operation'      => now(),
                'operation_parent_id' => null,
                'description'         => "Remboursement — {$motif}",
                'libelle'             => "Montant prélèvement {$typeCotisation->libelle} remboursé",
            ]);

            $remboursement = Remboursement::create([
                'operation_id'               => $operation->id,
                'operation_remboursement_id' => $operationRemboursement->id,
                'user_id'                    => $operation->user_id,
                'type_cotisation_id'         => $operation->type_cotisation_id,
                'administrateur_id'          => $admin->id,
                'montant_preleve'            => $operation->montant,
                'montant_rembourse'          => $montant,
                'motif'                      => $motif,
            ]);

            $this->cotisationService->reverserVersement(
                $operation->user,
                $operation->type_cotisation_id,
                $montant,
                $operation->date_operation,
            );

            $this->notificationService->notifierRemboursement(
                $operation->user,
                $montant,
                $typeCotisation->libelle,
            );

            AuditLogger::log(
                'REMBOURSEMENT.CREATE',
                $admin,
                'operations',
                (string) $operation->id,
                [
                    'operation_id'   => $operation->id,
                    'type_cotisation'=> $typeCotisation->libelle,
                    'user_id'        => $operation->user_id,
                    'montant_preleve'=> (string) $operation->montant,
                ],
                [
                    'remboursement_id'           => $remboursement->id,
                    'operation_remboursement_id' => $operationRemboursement->id,
                    'montant_rembourse'          => (string) $montant,
                    'motif'                      => $motif,
                ],
            );

            return $remboursement->load(['operation', 'operationRemboursement', 'user', 'typeCotisation', 'administrateur']);
        });
    }
}
