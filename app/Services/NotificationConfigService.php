<?php

namespace App\Services;

use App\Models\Administrateur;
use App\Models\DeviceToken;
use App\Models\NotificationConfig;
use App\Services\Push\PushMessagingFactory;
use App\Services\Sms\SmsProviderFactory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;

class NotificationConfigService
{
    private const CANAUX = ['SMS', 'EMAIL', 'PUSH', 'IN_APP'];
    private const CACHE_TTL = 300; // 5 min

    public function __construct(
        private SmsProviderFactory $smsProviderFactory,
        private PushMessagingFactory $pushMessagingFactory,
    ) {
    }

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
            'SMS'   => $this->testerSMS([...$conf, 'fournisseur' => $cfg->fournisseur]),
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
        $testPhone = $conf['test_phone'] ?? null;

        if (!$testPhone) {
            return ['success' => false, 'message' => "Aucun téléphone de test configuré (configuration.test_phone)."];
        }

        $provider = $this->smsProviderFactory->resoudre($conf['fournisseur'] ?? null);

        return $provider->envoyer(
            $testPhone,
            'Test SMS E-BEB Finance — ' . now()->format('d/m/Y H:i'),
            $conf,
        );
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

    /**
     * Les jetons d'appareil (`device_tokens`) sont rattachés aux utilisateurs
     * mobile (`User`), pas aux administrateurs — l'application mobile n'étant
     * pas utilisée côté panel admin, un administrateur n'a jamais lui-même de
     * jeton. Le test envoie donc au jeton le plus récemment enregistré dans le
     * système, ce qui valide réellement la configuration Firebase ; un message
     * clair invite à se connecter depuis un compte utilisateur réel si aucun
     * jeton n'existe encore.
     */
    private function testerPush(array $conf): array
    {
        $deviceToken = DeviceToken::latest()->first();

        if (!$deviceToken) {
            return [
                'success' => false,
                'message' => "Aucun appareil mobile enregistré pour le moment. Connectez-vous à l'application mobile avec un compte utilisateur réel pour générer un jeton, puis relancez le test.",
            ];
        }

        try {
            $messaging = $this->pushMessagingFactory->depuisConfiguration($conf);

            $message = CloudMessage::new()
                ->withNotification(FirebaseNotification::create(
                    'Test Push — E-BEB Finance',
                    'Test de notification push ' . now()->format('d/m/Y H:i'),
                ))
                ->withToken($deviceToken->token);

            $messaging->send($message);

            return ['success' => true, 'message' => 'Notification push test envoyée à un appareil enregistré.'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Échec push : ' . $e->getMessage()];
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
            'PUSH'  => ['service_account_json'],
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
            'PUSH'  => ['service_account_json'],
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
