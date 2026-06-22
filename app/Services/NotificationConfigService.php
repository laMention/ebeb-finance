<?php

namespace App\Services;

use App\Models\Administrateur;
use App\Models\NotificationConfig;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationConfigService
{
    private const CANAUX = ['SMS', 'EMAIL', 'PUSH', 'IN_APP'];
    private const CACHE_TTL = 300; // 5 min

    // ─────────────────────────────────────────────────────────────────────────
    // Lecture
    // ─────────────────────────────────────────────────────────────────────────

    public function tous(): Collection
    {
        $stored = NotificationConfig::all()->keyBy('canal');

        return collect(self::CANAUX)->map(function (string $canal) use ($stored) {
            $config = $stored->get($canal);

            if (!$config) {
                return [
                    'canal'          => $canal,
                    'est_actif'      => false,
                    'fournisseur'    => null,
                    'configuration'  => [],
                ];
            }

            return [
                'canal'         => $config->canal,
                'est_actif'     => $config->est_actif,
                'fournisseur'   => $config->fournisseur,
                'configuration' => $this->sanitizeConfig($canal, $config->configuration),
            ];
        });
    }

    public function getParCanal(string $canal): array
    {
        $config = NotificationConfig::where('canal', strtoupper($canal))->first();
        if (!$config) {
            return ['canal' => strtoupper($canal), 'est_actif' => false, 'fournisseur' => null, 'configuration' => []];
        }

        return [
            'canal'         => $config->canal,
            'est_actif'     => $config->est_actif,
            'fournisseur'   => $config->fournisseur,
            'configuration' => $this->sanitizeConfig($canal, $config->configuration),
        ];
    }

    public function estActif(string $canal): bool
    {
        $canalUp = strtoupper($canal);

        return Cache::remember("notif_canal_actif_{$canalUp}", self::CACHE_TTL, function () use ($canalUp) {
            return NotificationConfig::where('canal', $canalUp)->where('est_actif', true)->exists();
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Écriture
    // ─────────────────────────────────────────────────────────────────────────

    public function sauvegarder(string $canal, array $data): array
    {
        $canalUp = strtoupper($canal);

        $config = NotificationConfig::firstOrNew(['canal' => $canalUp]);
        $config->fournisseur = $data['fournisseur'] ?? $config->fournisseur;

        if (!empty($data['configuration'])) {
            $existing = $config->exists ? $config->configuration : [];
            $merged   = $this->mergeConfig($canalUp, $existing, $data['configuration']);
            $config->configuration = $merged;
        }

        $config->save();

        $this->invaliderCache($canalUp);

        return $this->getParCanal($canalUp);
    }

    public function basculerStatut(string $canal): array
    {
        $canalUp = strtoupper($canal);

        $config = NotificationConfig::firstOrCreate(['canal' => $canalUp], [
            'est_actif' => false, 'fournisseur' => null, 'configuration' => [],
        ]);

        $config->est_actif = !$config->est_actif;
        $config->save();

        $this->invaliderCache($canalUp);

        return $this->getParCanal($canalUp);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Test d'envoi
    // ─────────────────────────────────────────────────────────────────────────

    public function testerEnvoi(string $canal, ?Administrateur $admin): array
    {
        $canalUp = strtoupper($canal);
        $cfg     = NotificationConfig::where('canal', $canalUp)->first();

        if (!$cfg) {
            return ['success' => false, 'message' => "Canal {$canalUp} non configuré."];
        }

        $conf = $cfg->configuration;

        return match ($canalUp) {
            'SMS'   => $this->testerSMS($conf),
            'EMAIL' => $this->testerEmail($conf, $admin),
            'PUSH'  => $this->testerPush($conf),
            'IN_APP'=> ['success' => true, 'message' => 'Canal In-App opérationnel.'],
            default => ['success' => false, 'message' => "Canal inconnu : {$canalUp}"],
        };
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Tests internes
    // ─────────────────────────────────────────────────────────────────────────

    private function testerSMS(array $conf): array
    {
        $apiUrl  = $conf['api_url']  ?? null;
        $apiKey  = $conf['api_key']  ?? null;
        $sender  = $conf['sender_id'] ?? 'E-BEB';

        if (!$apiUrl || !$apiKey) {
            return ['success' => false, 'message' => 'Configuration SMS incomplète (api_url ou api_key manquant).'];
        }

        try {
            $response = Http::withToken($apiKey)->timeout(10)->post($apiUrl, [
                'sender'  => $sender,
                'message' => 'Test SMS E-BEB Finance — ' . now()->format('d/m/Y H:i'),
                'to'      => $conf['test_phone'] ?? '00000000',
            ]);

            if ($response->successful()) {
                return ['success' => true, 'message' => 'SMS test envoyé avec succès.'];
            }

            return ['success' => false, 'message' => 'Échec SMS : ' . ($response->json('message') ?? $response->status())];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Erreur connexion SMS : ' . $e->getMessage()];
        }
    }

    private function testerEmail(array $conf, ?Administrateur $admin): array
    {
        if (empty($conf['host']) || empty($conf['username'])) {
            return ['success' => false, 'message' => 'Configuration SMTP incomplète.'];
        }

        $destinataire = $admin?->email ?? $conf['from_address'] ?? null;
        if (!$destinataire) {
            return ['success' => false, 'message' => 'Aucun destinataire disponible pour le test.'];
        }

        try {
            // Config SMTP dynamique
            Config::set('mail.mailers.smtp.host',       $conf['host']);
            Config::set('mail.mailers.smtp.port',       (int) ($conf['port'] ?? 587));
            Config::set('mail.mailers.smtp.username',   $conf['username']);
            Config::set('mail.mailers.smtp.password',   $conf['password'] ?? '');
            Config::set('mail.mailers.smtp.encryption', $conf['encryption'] ?? 'tls');
            Config::set('mail.from.address',             $conf['from_address'] ?? $conf['username']);
            Config::set('mail.from.name',                $conf['from_name'] ?? 'E-BEB Finance');

            Mail::raw(
                "Email de test E-BEB Finance — " . now()->format('d/m/Y H:i') . "\n\nVotre configuration SMTP est opérationnelle.",
                fn($m) => $m->to($destinataire)->subject('Test Email — E-BEB Finance')
            );

            return ['success' => true, 'message' => "Email test envoyé à {$destinataire}."];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Erreur SMTP : ' . $e->getMessage()];
        }
    }

    private function testerPush(array $conf): array
    {
        $apiKey    = $conf['api_key'] ?? null;
        $projectId = $conf['project_id'] ?? null;

        if (!$apiKey) {
            return ['success' => false, 'message' => 'Configuration Push incomplète (api_key manquant).'];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => "key={$apiKey}",
                'Content-Type'  => 'application/json',
            ])->timeout(10)->post('https://fcm.googleapis.com/fcm/send', [
                'to'           => '/topics/test',
                'notification' => [
                    'title' => 'Test Push — E-BEB Finance',
                    'body'  => 'Test de notification push ' . now()->format('d/m/Y H:i'),
                ],
            ]);

            if ($response->successful()) {
                return ['success' => true, 'message' => 'Notification push test envoyée.'];
            }

            return ['success' => false, 'message' => 'Échec push : ' . ($response->json('error') ?? $response->status())];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Erreur connexion push : ' . $e->getMessage()];
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    /** Masque les champs sensibles pour la réponse API. */
    private function sanitizeConfig(string $canal, array $conf): array
    {
        $sensitive = match (strtoupper($canal)) {
            'SMS'   => ['api_key', 'api_secret'],
            'EMAIL' => ['password'],
            'PUSH'  => ['api_key'],
            default => [],
        };

        $safe = $conf;
        foreach ($sensitive as $key) {
            if (isset($safe[$key]) && $safe[$key] !== '') {
                $safe[$key] = '••••••••';
            }
        }

        return $safe;
    }

    /** Fusionne la config existante avec les nouvelles valeurs — ne remplace pas les champs masqués '••••••••'. */
    private function mergeConfig(string $canal, array $existing, array $incoming): array
    {
        $sensitive = match ($canal) {
            'SMS'   => ['api_key', 'api_secret'],
            'EMAIL' => ['password'],
            'PUSH'  => ['api_key'],
            default => [],
        };

        $merged = array_merge($existing, $incoming);

        // Restaurer les valeurs existantes pour les champs masqués non modifiés
        foreach ($sensitive as $key) {
            if (($incoming[$key] ?? '') === '••••••••') {
                $merged[$key] = $existing[$key] ?? '';
            }
        }

        return $merged;
    }

    private function invaliderCache(string $canal): void
    {
        Cache::forget("notif_canal_actif_{$canal}");
    }
}
