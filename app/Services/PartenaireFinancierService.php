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
            'nom'                       => $data['nom'],
            'code'                      => strtoupper(trim($data['code'])),
            'type'                      => $data['type'],
            'est_actif'                 => $data['est_actif'] ?? true,
            'url_api_reversement'       => $data['url_api_reversement'] ?? null,
            'url_api_consultation'      => $data['url_api_consultation'] ?? null,
            'url_webhook'               => $data['url_webhook'] ?? null,
            'methode_authentification'  => $data['methode_authentification'] ?? null,
            'identifiants_api'          => $data['identifiants_api'] ?? null,
            'format_echange'            => $data['format_echange'] ?? 'JSON',
        ]);
    }

    /**
     * Met à jour un partenaire existant (champs fournis seulement).
     */
    public function modifier(PartenairesFinancier $partenaire, array $data): PartenairesFinancier
    {
        $champs = [];

        foreach ([
            'nom', 'type', 'est_actif', 'url_api_reversement', 'url_api_consultation',
            'url_webhook', 'methode_authentification', 'identifiants_api', 'format_echange',
        ] as $champ) {
            if (array_key_exists($champ, $data)) {
                $champs[$champ] = $data[$champ];
            }
        }

        if (isset($data['code'])) {
            $champs['code'] = strtoupper(trim($data['code']));
        }

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
