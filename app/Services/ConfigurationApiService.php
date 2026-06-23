<?php

namespace App\Services;

use App\Http\Resources\ConfigurationApiResource;
use App\Models\ConfigurationApiOperateur;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ConfigurationApiService
{
    public function lister(array $params): array
    {
        try {
            $query = ConfigurationApiOperateur::query()->with('moyen_paiement');

            if (!empty($params['search'])) {
                $q = $params['search'];
                $query->where(function ($qb) use ($q) {
                    $qb->where('url_api', 'like', "%{$q}%")
                       ->orWhereHas('moyen_paiement', fn ($mq) =>
                           $mq->where('libelle', 'like', "%{$q}%")
                              ->orWhere('operateur', 'like', "%{$q}%")
                              ->orWhere('code', 'like', "%{$q}%")
                       );
                });
            }

            if (!empty($params['moyen_paiement_id'])) {
                $query->where('moyen_paiement_id', $params['moyen_paiement_id']);
            }

            if (!empty($params['environnement'])) {
                $query->where('environnement', $params['environnement']);
            }

            if (isset($params['est_actif']) && $params['est_actif'] !== '') {
                $query->where('est_actif', filter_var($params['est_actif'], FILTER_VALIDATE_BOOLEAN));
            }

            $perPage   = min((int) ($params['per_page'] ?? 10), 50);
            $paginator = $query->orderBy('environnement')->paginate($perPage);

            return [
                'success' => true,
                'message' => 'Configurations API récupérées avec succès',
                'data'    => ConfigurationApiResource::collection($paginator->items())->toArray(request()),
                'meta'    => [
                    'current_page' => $paginator->currentPage(),
                    'per_page'     => $paginator->perPage(),
                    'total'        => $paginator->total(),
                    'last_page'    => $paginator->lastPage(),
                ],
            ];
        } catch (\Exception $e) {
            Log::error('Erreur listage configurations API', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Erreur : ' . $e->getMessage()];
        }
    }

    public function creer(array $data): array
    {
        try {
            if (!empty($data['est_actif'])) {
                $this->desactiverAutres($data['moyen_paiement_id'], $data['environnement']);
            }

            $config = ConfigurationApiOperateur::create([
                'moyen_paiement_id' => $data['moyen_paiement_id'],
                'url_api'           => $data['url_api'],
                'url_webhook'       => $data['url_webhook'],
                'identifiant_api'   => $data['identifiant_api'] ?? null,
                'cle_api'           => !empty($data['cle_api']) ? $data['cle_api'] : null,
                'environnement'     => $data['environnement'],
                'est_actif'         => (bool) ($data['est_actif'] ?? false),
                'notes'             => $data['notes'] ?? null,
            ]);

            $config->load('moyen_paiement');

            Log::info('Configuration API créée', ['id' => $config->id, 'moyen_paiement_id' => $config->moyen_paiement_id]);

            return [
                'success' => true,
                'message' => 'Configuration API créée avec succès',
                'data'    => new ConfigurationApiResource($config),
            ];
        } catch (\Exception $e) {
            Log::error('Erreur création configuration API', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Erreur : ' . $e->getMessage()];
        }
    }

    public function modifier(ConfigurationApiOperateur $config, array $data): array
    {
        try {
            $champs = [];

            foreach (['moyen_paiement_id', 'url_api', 'url_webhook', 'identifiant_api', 'environnement', 'notes'] as $champ) {
                if (array_key_exists($champ, $data)) {
                    $champs[$champ] = $data[$champ];
                }
            }

            if (array_key_exists('cle_api', $data)) {
                $champs['cle_api'] = !empty($data['cle_api']) ? $data['cle_api'] : null;
            }

            if (array_key_exists('est_actif', $data)) {
                $champs['est_actif'] = (bool) $data['est_actif'];
            }

            if (!empty($champs['est_actif'])) {
                $moyenPaiementId = $champs['moyen_paiement_id'] ?? $config->moyen_paiement_id;
                $environnement   = $champs['environnement']     ?? $config->environnement;
                $this->desactiverAutres($moyenPaiementId, $environnement, $config->id);
            }

            $config->update($champs);
            $config->load('moyen_paiement');

            Log::info('Configuration API modifiée', ['id' => $config->id]);

            return [
                'success' => true,
                'message' => 'Configuration API modifiée avec succès',
                'data'    => new ConfigurationApiResource($config->fresh()->load('moyen_paiement')),
            ];
        } catch (\Exception $e) {
            Log::error('Erreur modification configuration API', ['id' => $config->id, 'error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Erreur : ' . $e->getMessage()];
        }
    }

    public function basculerStatut(ConfigurationApiOperateur $config): array
    {
        try {
            $nouvelEtat = !$config->est_actif;

            if ($nouvelEtat) {
                $this->desactiverAutres($config->moyen_paiement_id, $config->environnement, $config->id);
            }

            $config->update(['est_actif' => $nouvelEtat]);
            $config->load('moyen_paiement');

            $statut = $nouvelEtat ? 'activée' : 'désactivée';
            Log::info("Configuration API {$statut}", ['id' => $config->id]);

            return [
                'success'   => true,
                'message'   => "Configuration API {$statut} avec succès",
                'est_actif' => $nouvelEtat,
                'data'      => new ConfigurationApiResource($config->fresh()->load('moyen_paiement')),
            ];
        } catch (\Exception $e) {
            Log::error('Erreur bascule statut configuration API', ['id' => $config->id, 'error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Erreur : ' . $e->getMessage()];
        }
    }

    public function supprimer(ConfigurationApiOperateur $config): array
    {
        try {
            if ($config->est_actif) {
                return [
                    'success' => false,
                    'message' => 'Impossible de supprimer une configuration active. Désactivez-la d\'abord.',
                ];
            }

            $config->delete();
            Log::info('Configuration API supprimée', ['id' => $config->id]);

            return ['success' => true, 'message' => 'Configuration API supprimée avec succès'];
        } catch (\Exception $e) {
            Log::error('Erreur suppression configuration API', ['id' => $config->id, 'error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Erreur : ' . $e->getMessage()];
        }
    }

    public function testerConnexion(ConfigurationApiOperateur $config): array
    {
        try {
            $response = Http::timeout(10)->withoutVerifying()->head($config->url_api);

            return [
                'success'     => true,
                'message'     => "Connexion réussie (HTTP {$response->status()})",
                'status_code' => $response->status(),
                'url'         => $config->url_api,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Échec de connexion : ' . $e->getMessage(),
                'url'     => $config->url_api,
            ];
        }
    }

    public function testerWebhook(ConfigurationApiOperateur $config): array
    {
        try {
            $response = Http::timeout(10)
                ->withoutVerifying()
                ->post($config->url_webhook, ['event' => 'ping', 'source' => 'ebeb-finance-test']);

            return [
                'success'     => $response->status() < 500,
                'message'     => "Webhook joignable (HTTP {$response->status()})",
                'status_code' => $response->status(),
                'url'         => $config->url_webhook,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Webhook inaccessible : ' . $e->getMessage(),
                'url'     => $config->url_webhook,
            ];
        }
    }

    // ---------------------------------------------------------------------------
    // Helpers privés
    // ---------------------------------------------------------------------------

    private function desactiverAutres(string $moyenPaiementId, string $environnement, ?string $exceptId = null): void
    {
        $query = ConfigurationApiOperateur::where('moyen_paiement_id', $moyenPaiementId)
            ->where('environnement', $environnement)
            ->where('est_actif', true);

        if ($exceptId) {
            $query->where('id', '!=', $exceptId);
        }

        $query->update(['est_actif' => false]);
    }
}
