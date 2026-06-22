<?php

namespace App\Services;

use App\Models\ParametreGlobal;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ParametreGlobalService
{
    // ─────────────────────────────────────────────────────────────────────────
    // Référentiel des clés connues + valeurs par défaut
    // ─────────────────────────────────────────────────────────────────────────

    public const DEFAULTS = [
        'TAUX_COMMISSION'             => '3.0',   // Commission plateforme (%)
        'COTISATION_CNPS_MIN'         => '5000',  // Plancher mensuel CNPS (FCFA)
        'COTISATION_AMU_MIN'          => '2500',  // Plancher mensuel AMU (FCFA)
        'OTP_DUREE_SECONDES'          => '300',   // Durée validité OTP (secondes)
        'OTP_TENTATIVES_MAX'          => '3',     // Nb max tentatives OTP
        'OTP_REQUIS_KYC'              => '1',     // OTP requis pour KYC
        'ESCROW_DELAI_ALERTE_HEURES'  => '24',   // Délai escrow avant alerte
        'ALERTE_CRITIQUE_TEMPS_REEL'  => '1',    // Alertes critiques temps réel
        'SERVICE_WAVE'                => '1',     // Encaissement Wave
        'SERVICE_ORANGE_MONEY'        => '1',     // Encaissement Orange Money
        'SERVICE_MTN'                 => '1',     // Encaissement MTN Money
        'SERVICE_MOOV'                => '1',     // Encaissement Moov Money
        'SERVICE_REVERSEMENT_CNPS'    => '1',     // Reversement automatique CNPS
        'NOTIF_SMS'                   => '1',     // Notifications SMS
        'NOTIF_EMAIL'                 => '0',     // Notifications par email
    ];

    private const CACHE_KEY = 'parametre_globals_tous';
    private const CACHE_TTL = 300; // 5 minutes

    // ─────────────────────────────────────────────────────────────────────────
    // Accès statique (utilisable depuis n'importe quel service)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Retourne la valeur d'un paramètre (avec cache + fallback sur défaut).
     */
    public static function get(string $cle, ?string $default = null): ?string
    {
        $all = Cache::remember(self::CACHE_KEY, self::CACHE_TTL, fn() =>
            ParametreGlobal::pluck('valeur', 'cle')->toArray()
        );

        return $all[$cle] ?? $default ?? self::DEFAULTS[$cle] ?? null;
    }

    /**
     * Retourne true si le paramètre booléen est activé ("1" ou "true").
     */
    public static function estActif(string $cle): bool
    {
        $val = self::get($cle, '1');
        return $val === '1' || $val === 'true';
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Lecture structurée de tous les paramètres
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Retourne tous les paramètres connus avec leur valeur (DB ou défaut).
     */
    public function getTous(): array
    {
        $rows = ParametreGlobal::pluck('valeur', 'cle');
        $result = [];
        foreach (self::DEFAULTS as $cle => $default) {
            $result[$cle] = $rows[$cle] ?? $default;
        }
        return $result;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Sauvegarde groupée
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Upsert tous les paramètres en une transaction + invalide le cache.
     */
    public function sauvegarderTous(array $data, string $adminId): array
    {
        DB::transaction(function () use ($data, $adminId) {
            foreach ($data as $cle => $valeur) {
                if (!array_key_exists($cle, self::DEFAULTS)) {
                    continue;
                }
                // Convertir booléens PHP en "1"/"0"
                if (is_bool($valeur)) {
                    $valeur = $valeur ? '1' : '0';
                }
                ParametreGlobal::updateOrCreate(
                    ['cle' => $cle],
                    ['valeur' => (string) $valeur, 'modifie_par' => $adminId]
                );
            }
        });

        Cache::forget(self::CACHE_KEY);

        return $this->getTous();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Méthodes CRUD individuelles (conservées pour rétro-compatibilité)
    // ─────────────────────────────────────────────────────────────────────────

    public function listerParametresGlobaux(array $filtres = []): array
    {
        try {
            $query = ParametreGlobal::query();
            if (isset($filtres['cle'])) {
                $query->where('cle', 'like', "%{$filtres['cle']}%");
            }
            if (isset($filtres['valeur'])) {
                $query->where('valeur', 'like', "%{$filtres['valeur']}%");
            }
            $parametres = $query->orderBy('cle')->get();

            return ['success' => true, 'message' => 'Paramètres globaux récupérés.', 'data' => $parametres, 'total' => $parametres->count()];
        } catch (\Exception $e) {
            \Log::error('Erreur listage paramètres', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => $e->getMessage(), 'data' => []];
        }
    }

    public function obtenirParametreGlobal(string $id): array
    {
        $parametre = ParametreGlobal::find($id);
        if (!$parametre) {
            return ['success' => false, 'message' => 'Paramètre non trouvé.', 'data' => null];
        }
        return ['success' => true, 'message' => 'Paramètre récupéré.', 'data' => $parametre];
    }

    public function creerParametreGlobal(array $data, ?string $adminId = null): array
    {
        try {
            $cle = strtoupper($data['cle']);
            if (ParametreGlobal::where('cle', $cle)->exists()) {
                return ['success' => false, 'message' => "La clé '{$cle}' existe déjà."];
            }
            $parametre = ParametreGlobal::create(['cle' => $cle, 'valeur' => $data['valeur'], 'description' => $data['description'] ?? null, 'modifie_par' => $adminId]);
            Cache::forget(self::CACHE_KEY);
            return ['success' => true, 'message' => 'Paramètre créé.', 'data' => $parametre];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function modifierParametreGlobal(ParametreGlobal $parametre, array $data, ?string $adminId = null): array
    {
        try {
            $champs = array_filter([
                'cle'         => isset($data['cle']) ? strtoupper($data['cle']) : null,
                'valeur'      => $data['valeur'] ?? null,
                'description' => $data['description'] ?? null,
                'modifie_par' => $adminId,
            ], fn($v) => $v !== null);

            if (empty($champs)) {
                return ['success' => false, 'message' => 'Aucune donnée à mettre à jour.'];
            }
            $parametre->update($champs);
            Cache::forget(self::CACHE_KEY);
            return ['success' => true, 'message' => 'Paramètre modifié.', 'data' => $parametre->fresh()];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function supprimerParametreGlobal(ParametreGlobal $parametre): array
    {
        try {
            $parametre->delete();
            Cache::forget(self::CACHE_KEY);
            return ['success' => true, 'message' => 'Paramètre supprimé.'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
