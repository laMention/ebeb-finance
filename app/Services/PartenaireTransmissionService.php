<?php

namespace App\Services;

use App\Models\PartenairesFinancier;
use App\Models\Reversement;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PartenaireTransmissionService
{
    public function __construct(
        private readonly PartenairePayloadService $payloadService,
    ) {}

    // -------------------------------------------------------------------------
    // Vérification de la configuration avant validation d'un reversement
    // -------------------------------------------------------------------------

    public function verifierConfiguration(PartenairesFinancier $partenaire): array
    {
        $erreurs = [];

        if (!$partenaire->est_actif) {
            $erreurs[] = 'Le partenaire est inactif.';
        }

        if (empty($partenaire->url_api_reversement)) {
            $erreurs[] = "L'URL de l'API de reversement n'est pas configurée.";
        }

        if (empty($partenaire->methode_authentification)) {
            $erreurs[] = "La méthode d'authentification n'est pas configurée.";
        } else {
            $identifiants = $partenaire->identifiants_api ?? [];
            $requis = match ($partenaire->methode_authentification) {
                'API_KEY'    => ['api_key'],
                'BASIC_AUTH' => ['username', 'password'],
                'OAUTH'      => ['client_id', 'client_secret', 'token_url'],
                default      => [],
            };

            foreach ($requis as $champ) {
                if (empty($identifiants[$champ])) {
                    $erreurs[] = "Identifiant d'authentification manquant : {$champ}.";
                }
            }
        }

        if ($partenaire->comptesDestination()->where('est_actif', true)->doesntExist()) {
            $erreurs[] = "Aucun compte de destination actif n'est configuré pour ce partenaire.";
        }

        return ['ok' => empty($erreurs), 'erreurs' => $erreurs];
    }

    // -------------------------------------------------------------------------
    // Transmission au partenaire
    // -------------------------------------------------------------------------

    public function transmettre(Reversement $reversement, PartenairesFinancier $partenaire, Collection $operations): array
    {
        $travailleurs = $operations
            ->map(fn ($op) => $this->payloadService->construirePourOperation($op))
            ->filter()
            ->values();

        $payload = [
            'reversement' => [
                'reference'        => $reversement->reference,
                'montant_total'    => (float) $reversement->montant_total,
                'date_reversement' => $reversement->date_reversement?->format('Y-m-d H:i:s'),
                'periode_debut'    => $reversement->periode_debut?->format('Y-m-d'),
                'periode_fin'      => $reversement->periode_fin?->format('Y-m-d'),
            ],
            'partenaire' => [
                'nom'  => $partenaire->nom,
                'code' => $partenaire->code,
                'type' => $partenaire->type,
            ],
            'travailleurs_independants' => $travailleurs,
        ];

        $checksum = hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE));

        $reversement->update([
            'donnees_transmises' => $payload,
            'donnees_checksum'   => $checksum,
        ]);

        return $this->envoyer($reversement, $partenaire, $payload, $checksum);
    }

    public function retransmettre(Reversement $reversement): array
    {
        $partenaire = $reversement->partenaire;

        if (!$partenaire) {
            return ['success' => false, 'message' => 'Partenaire introuvable pour ce reversement.'];
        }

        $payload = $reversement->donnees_transmises;

        if (empty($payload)) {
            // Aucun instantané disponible (ancien reversement) — reconstruire depuis les opérations liées.
            $operations = $reversement->operations()->with(['user', 'type_cotisation'])->get();
            return $this->transmettre($reversement, $partenaire, $operations);
        }

        $checksum = $reversement->donnees_checksum ?? hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE));

        return $this->envoyer($reversement, $partenaire, $payload, $checksum);
    }

    // -------------------------------------------------------------------------
    // Helpers privés
    // -------------------------------------------------------------------------

    private function envoyer(Reversement $reversement, PartenairesFinancier $partenaire, array $payload, string $checksum): array
    {
        try {
            $requete = Http::timeout(15)->withoutVerifying()->acceptJson()
                ->withHeaders(['X-Donnees-Checksum' => $checksum]);

            $requete = $this->appliquerAuthentification($requete, $partenaire);

            $reponse = $requete->post($partenaire->url_api_reversement, $payload);

            $succes = $reponse->successful();

            $reversement->update([
                'transmission_statut'  => $succes ? Reversement::TRANSMISSION_TRANSMIS : Reversement::TRANSMISSION_ECHEC,
                'transmission_reponse' => [
                    'status_code' => $reponse->status(),
                    'corps'       => $reponse->json() ?? $reponse->body(),
                ],
                'transmission_date' => now(),
            ]);

            return [
                'success' => $succes,
                'message' => $succes
                    ? 'Transmission au partenaire réussie.'
                    : "Le partenaire a répondu avec le statut HTTP {$reponse->status()}.",
            ];
        } catch (\Throwable $e) {
            Log::warning('partenaire-transmission-echec', [
                'reversement_id' => $reversement->id,
                'partenaire_id'  => $partenaire->id,
                'erreur'         => $e->getMessage(),
            ]);

            $reversement->update([
                'transmission_statut'  => Reversement::TRANSMISSION_ECHEC,
                'transmission_reponse' => ['erreur' => $e->getMessage()],
                'transmission_date'    => now(),
            ]);

            return ['success' => false, 'message' => 'Échec de la transmission au partenaire : ' . $e->getMessage()];
        }
    }

    private function appliquerAuthentification(PendingRequest $requete, PartenairesFinancier $partenaire): PendingRequest
    {
        $identifiants = $partenaire->identifiants_api ?? [];

        return match ($partenaire->methode_authentification) {
            'API_KEY'    => $requete->withHeaders([($identifiants['header'] ?? 'X-Api-Key') => $identifiants['api_key'] ?? '']),
            'BASIC_AUTH' => $requete->withBasicAuth($identifiants['username'] ?? '', $identifiants['password'] ?? ''),
            'OAUTH'      => $requete->withToken($this->obtenirJetonOAuth($identifiants)),
            default      => $requete,
        };
    }

    private function obtenirJetonOAuth(array $identifiants): string
    {
        if (!empty($identifiants['access_token'])) {
            return $identifiants['access_token'];
        }

        if (empty($identifiants['token_url'])) {
            return '';
        }

        $reponse = Http::asForm()->post($identifiants['token_url'], [
            'grant_type'    => 'client_credentials',
            'client_id'     => $identifiants['client_id'] ?? '',
            'client_secret' => $identifiants['client_secret'] ?? '',
        ]);

        return $reponse->json('access_token') ?? '';
    }
}
