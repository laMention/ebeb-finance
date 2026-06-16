<?php

namespace App\Services;

use App\Models\Cotisation;
use App\Models\DeclarationRevenu;
use App\Models\Operation;
use App\Models\SoldeCategorie;
use App\Models\TypeCotisation;
use App\Models\User;
use Illuminate\Support\Str;

class CotisationService
{
    public function __construct(private NotificationService $notificationService) {}
    /**
     * Enregistre un versement de cotisation suite à un paiement entrant.
     * Gère la détection du dépassement de l'objectif annuel et la création du report.
     */
    public function enregistrerVersement(
        User $user,
        string $typeCotisationId,
        TypeCotisation $typeCotisation,
        float $montant,
        ?DeclarationRevenu $declaration,
        ?string $paiementEntrantId = null,
        ?string $operationParentId = null
    ): Cotisation {
        $mois  = now()->month;
        $annee = now()->year;

        $objectifMensuel = $this->calculerObjectifMensuel($typeCotisation, $declaration);
        $objectifAnnuel  = $objectifMensuel * 12;

        $cotisation = Cotisation::firstOrCreate(
            [
                'user_id'            => $user->id,
                'type_cotisation_id' => $typeCotisationId,
                'mois'               => $mois,
                'annee'              => $annee,
            ],
            [
                'montant_verse'    => 0,
                'montant_objectif' => $objectifMensuel,
                'montant_restant'  => $objectifMensuel,
                'statut'           => 'EN_COURS',
                'numero_adherent'  => $this->numeroAdherent($user, $typeCotisation),
                'date_debut'       => now()->startOfMonth()->toDateString(),
                'date_fin'         => now()->endOfMonth()->toDateString(),
                'date_paiement'    => null,
            ]
        );

        // Calcul du total annuel APRÈS ce versement
        $totalAnnuelAvant = (float) Cotisation::where('user_id', $user->id)
            ->where('type_cotisation_id', $typeCotisationId)
            ->where('annee', $annee)
            ->where('statut', '!=', 'REPORT')
            ->sum('montant_verse');

        $montantAImputer = $montant;
        $montantReport   = 0.0;

        if ($objectifAnnuel > 0) {
            $resteAnnuel = max(0, $objectifAnnuel - $totalAnnuelAvant);
            if ($montant > $resteAnnuel) {
                $montantAImputer = $resteAnnuel;
                $montantReport   = round($montant - $resteAnnuel, 2);
            }
        }

        $nouveauVerse   = (float) $cotisation->montant_verse + $montantAImputer;
        $montantRestant = max(0, (float) $cotisation->montant_objectif - $nouveauVerse);

        $cotisation->update([
            'montant_verse'   => $nouveauVerse,
            'montant_restant' => $montantRestant,
            'statut'          => $this->calculerStatut($nouveauVerse, (float) $cotisation->montant_objectif),
            'date_paiement'   => now(),
        ]);

        // Si objectif annuel atteint, marquer toutes les cotisations de l'année
        if ($objectifAnnuel > 0 && ($totalAnnuelAvant + $montantAImputer) >= $objectifAnnuel) {
            $this->marquerObjectifAnnuelAtteint($user, $typeCotisationId, $annee);
            $this->notificationService->notifierObjectifCotisationAtteint($user, $typeCotisation->libelle);
        }

        // Créer le report si dépassement
        if ($montantReport > 0) {
            $this->creerReport($user, $typeCotisationId, $typeCotisation, $montantReport, $paiementEntrantId, $operationParentId);
        }

        return $cotisation->fresh();
    }

    /**
     * Applique les reports de l'année précédente sur la première cotisation de la nouvelle année.
     * À appeler au premier paiement de chaque nouvelle année, ou via un job planifié.
     */
    public function appliquerReportsAnneesPrecedentes(User $user, string $typeCotisationId): void
    {
        $annee = now()->year;

        $report = SoldeCategorie::where('user_id', $user->id)
            ->where('type_cotisation_id', $typeCotisationId)
            ->where('total_reporte', '>', 0)
            ->lockForUpdate()
            ->first();

        if (!$report) return;

        $montantReport = (float) $report->total_reporte;

        // Vérifier si pas déjà appliqué cette année
        $dejaApplique = Cotisation::where('user_id', $user->id)
            ->where('type_cotisation_id', $typeCotisationId)
            ->where('annee', $annee)
            ->where('statut', 'REPORT')
            ->exists();

        if ($dejaApplique) return;

        // Créer une cotisation de report pour la nouvelle année
        Cotisation::create([
            'user_id'            => $user->id,
            'type_cotisation_id' => $typeCotisationId,
            'mois'               => 0,
            'annee'              => $annee,
            'montant_verse'      => $montantReport,
            'montant_objectif'   => 0,
            'montant_restant'    => 0,
            'statut'             => 'REPORT',
            'numero_adherent'    => $this->numeroAdherent($user, TypeCotisation::find($typeCotisationId)),
            'date_debut'         => null,
            'date_fin'           => null,
            'date_paiement'      => now(),
        ]);

        $report->update(['total_reporte' => 0]);
    }

    public function calculerObjectifMensuel(TypeCotisation $type, ?DeclarationRevenu $declaration): float
    {
        if (!$declaration) return 0;

        $code      = strtoupper($type->code ?? '');
        $categorie = strtoupper($type->categorie ?? '');

        if (str_contains($code, 'CNPS') || str_contains($categorie, 'CNPS')) {
            return round((float) $declaration->montant_cotisation_regime_base / 12, 2);
        }

        if (str_contains($code, 'AMU') || str_contains($categorie, 'AMU')) {
            return round((float) $declaration->montant_cotisation_mensuelle / 2, 2);
        }

        return 0;
    }

    private function calculerStatut(float $verse, float $objectif): string
    {
        if ($objectif <= 0) return 'EN_COURS';
        if ($verse <= 0)    return 'NON_A_JOUR';
        if ($verse >= $objectif) return 'OBJECTIF_ATTEINT';
        return 'EN_COURS';
    }

    private function marquerObjectifAnnuelAtteint(User $user, string $typeCotisationId, int $annee): void
    {
        Cotisation::where('user_id', $user->id)
            ->where('type_cotisation_id', $typeCotisationId)
            ->where('annee', $annee)
            ->whereNotIn('statut', ['OBJECTIF_ATTEINT', 'REPORT'])
            ->update(['statut' => 'OBJECTIF_ATTEINT']);
    }

    private function creerReport(
        User $user,
        string $typeCotisationId,
        TypeCotisation $typeCotisation,
        float $montantReport,
        ?string $paiementEntrantId,
        ?string $operationParentId
    ): void {
        // Stocker le report dans le solde catégorie pour la prochaine année
        $categorie = $this->resoudreCategorie($typeCotisation);

        $solde = SoldeCategorie::firstOrCreate(
            ['user_id' => $user->id, 'type_categorie' => $categorie, 'type_cotisation_id' => $typeCotisationId],
            ['libelle' => $typeCotisation->libelle, 'solde' => 0, 'total_verse' => 0, 'total_reporte' => 0]
        );
        $solde->increment('total_reporte', $montantReport);

        $this->notificationService->notifierReportCotisation($user, $montantReport, $typeCotisation->libelle);

        // Enregistrer l'opération de report
        if ($operationParentId) {
            Operation::create([
                'user_id'             => $user->id,
                'type_cotisation_id'  => $typeCotisationId,
                'paiement_entrant_id' => $paiementEntrantId,
                'montant'             => $montantReport,
                'type_operation'      => 'REPORT_COTISATION',
                'statut'              => 'SUCCES',
                'reference'           => 'RPT-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(6)),
                'date_operation'      => now(),
                'operation_parent_id' => $operationParentId,
                'description'         => "Report cotisation {$typeCotisation->libelle} vers " . (now()->year + 1),
                'libelle'             => 'Report cotisation',
            ]);
        }
    }

    private function numeroAdherent(User $user, ?TypeCotisation $type): string
    {
        // si le travailleur n'a pas encore de numero cnps ou amu utiliser sa reference utilisateur
        if (!$type) return $user->reference;
        $code = strtoupper($type->code ?? '');
        if (str_contains($code, 'CNPS')) return $user->numero_cnps ?? $user->reference;
        if (str_contains($code, 'AMU'))  return $user->numero_cmu  ?? $user->reference;
        return $user->reference;
    }

    private function resoudreCategorie(TypeCotisation $type): string
    {
        $code      = strtoupper($type->code ?? '');
        $categorie = strtoupper($type->categorie ?? '');
        if (str_contains($code, 'CNPS') || str_contains($categorie, 'CNPS')) return 'COTISATION_CNPS';
        if (str_contains($code, 'AMU')  || str_contains($categorie, 'AMU'))  return 'COTISATION_AMU';
        if (str_contains($categorie, 'ASSURANCE')) return 'ASSURANCE_PERSONNALISEE';
        return 'COTISATION_PERSONNALISEE';
    }
}
