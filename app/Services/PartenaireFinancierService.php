<?php

namespace App\Services;

use App\Http\Resources\PartenaireFinancierResource;
use App\Models\PartenairesFinancier;

class PartenaireFinancierService
{
    /**
     * Liste paginée avec recherche et filtre par type.
     */
    public function lister(array $params): array
    {
        $query = PartenairesFinancier::withCount('reversements')->orderBy('nom');

        if (!empty($params['recherche'])) {
            $r = $params['recherche'];
            $query->where(function ($q) use ($r) {
                $q->where('nom', 'like', "%{$r}%")
                  ->orWhere('code', 'like', "%{$r}%");
            });
        }

        if (!empty($params['type'])) {
            $query->where('type', $params['type']);
        }

        $perPage   = isset($params['per_page']) ? min((int) $params['per_page'], 100) : 20;
        $page      = isset($params['page'])     ? (int) $params['page'] : 1;
        $paginated = $query->paginate($perPage, ['*'], 'page', $page);

        return [
            'partenaires' => PartenaireFinancierResource::collection($paginated->getCollection()),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'per_page'     => $paginated->perPage(),
                'total'        => $paginated->total(),
                'from'         => $paginated->firstItem(),
                'to'           => $paginated->lastItem(),
            ],
        ];
    }

    /**
     * Crée un nouveau partenaire financier.
     */
    public function creer(array $data): PartenairesFinancier
    {
        return PartenairesFinancier::create([
            'nom'  => $data['nom'],
            'code' => strtoupper(trim($data['code'])),
            'type' => $data['type'],
        ]);
    }

    /**
     * Met à jour un partenaire existant (champs fournis seulement).
     */
    public function modifier(PartenairesFinancier $partenaire, array $data): PartenairesFinancier
    {
        $champs = [];

        if (isset($data['nom']))  $champs['nom']  = $data['nom'];
        if (isset($data['code'])) $champs['code'] = strtoupper(trim($data['code']));
        if (isset($data['type'])) $champs['type'] = $data['type'];

        if (!empty($champs)) {
            $partenaire->update($champs);
        }

        return $partenaire->loadCount('reversements');
    }

    /**
     * Supprime (soft-delete) un partenaire financier.
     */
    public function supprimer(PartenairesFinancier $partenaire): void
    {
        $partenaire->delete();
    }
}
