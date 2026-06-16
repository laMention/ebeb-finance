<?php

namespace App\Services;

use App\Models\PortefeuilleEpargne;
use App\Models\SoldeCategorie;
use App\Models\TypeCotisation;
use App\Models\User;

class PortefeuilleService
{
    /**
     * Met à jour le portefeuille mensuel et les soldes par catégorie après un paiement traité.
     */
    public function mettreAjourPortefeuille(User $user, array $repartition): void
    {
        $mois  = now()->month;
        $annee = now()->year;

        $portefeuille = PortefeuilleEpargne::firstOrCreate(
            ['user_id' => $user->id, 'mois_reference' => $mois, 'annee_reference' => $annee],
            [
                'total_epargne'            => 0,
                'solde_epargne_disponible' => 0,
                'total_verse_cotisations'  => 0,
                'total_commissions_payees' => 0,
                'total_recu_brut'          => 0,
                'montant_net_total'        => 0,
            ]
        );

        $portefeuille->increment('total_recu_brut',          $repartition['montant_brut']);
        $portefeuille->increment('total_epargne',            $repartition['epargne']);
        $portefeuille->increment('solde_epargne_disponible', $repartition['epargne']);
        $portefeuille->increment('total_verse_cotisations',  $repartition['total_cotisations']);
        $portefeuille->increment('total_commissions_payees', $repartition['commission']);
        $portefeuille->increment('montant_net_total',        $repartition['montant_net']);
        $portefeuille->update(['recalcule_at' => now()]);

        // Solde épargne
        if ($repartition['epargne'] > 0) {
            $this->mettreAjourSolde($user, 'EPARGNE', 'Épargne', null, $repartition['epargne']);
        }

        // Soldes cotisations
        foreach ($repartition['cotisations'] as $cot) {
            $type = $cot['type_cotisation'];
            $this->mettreAjourSolde(
                $user,
                $this->resoudreCategorie($type),
                $type->libelle,
                $cot['type_cotisation_id'],
                $cot['montant']
            );
        }
    }

    /**
     * Recalcule un portefeuille mensuel depuis l'historique des opérations.
     * Garantit la cohérence des agrégats.
     */
    public function recalculerDepuisHistorique(User $user, int $mois, int $annee): PortefeuilleEpargne
    {
        $debut = \Carbon\Carbon::create($annee, $mois, 1)->startOfMonth();
        $fin   = $debut->copy()->endOfMonth();

        $ops = \App\Models\Operation::where('user_id', $user->id)
            ->where('statut', 'SUCCES')
            ->whereBetween('date_operation', [$debut, $fin])
            ->get();

        $totaux = [
            'total_recu_brut'         => $ops->where('type_operation', 'PAIEMENT_CLIENT')->sum('montant'),
            'total_epargne'           => $ops->where('type_operation', 'EPARGNE')->sum('montant'),
            'total_verse_cotisations' => $ops->whereIn('type_operation', ['COTISATION_CNPS', 'COTISATION_AMU', 'COTISATION_PERSONNALISEE', 'ASSURANCE_PERSONNALISEE'])->sum('montant'),
            'total_commissions_payees'=> $ops->where('type_operation', 'COMMISSION_PLATEFORME')->sum('montant'),
        ];

        $totaux['montant_net_total'] = $totaux['total_recu_brut']
            - $totaux['total_epargne']
            - $totaux['total_verse_cotisations']
            - $totaux['total_commissions_payees'];

        $portefeuille = PortefeuilleEpargne::updateOrCreate(
            ['user_id' => $user->id, 'mois_reference' => $mois, 'annee_reference' => $annee],
            array_merge($totaux, ['recalcule_at' => now()])
        );

        return $portefeuille;
    }

    private function mettreAjourSolde(
        User $user,
        string $typeCategorie,
        string $libelle,
        ?string $typeCotisationId,
        float $montant
    ): void {
        $conditions = ['user_id' => $user->id, 'type_categorie' => $typeCategorie];
        if ($typeCotisationId) {
            $conditions['type_cotisation_id'] = $typeCotisationId;
        }

        $solde = SoldeCategorie::firstOrCreate(
            $conditions,
            ['libelle' => $libelle, 'solde' => 0, 'total_verse' => 0, 'total_reporte' => 0]
        );

        $solde->increment('solde', $montant);
        $solde->increment('total_verse', $montant);
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
