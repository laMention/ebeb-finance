<?php

namespace App\Services;

use App\Models\ParametreGeneral;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class ParametreGeneralService
{
    private const CACHE_KEY = 'parametre_general';
    private const CACHE_TTL = 300; // 5 minutes
    private const DISK      = 'public';
    private const DIR       = 'parametre-general';

    // ─────────────────────────────────────────────────────────────────────────
    // Accès statique (singleton)
    // ─────────────────────────────────────────────────────────────────────────

    /** Retourne les paramètres généraux (avec cache). Crée l'enregistrement s'il n'existe pas. */
    public static function getInstance(): ParametreGeneral
    {
        $cached = Cache::get(self::CACHE_KEY);

        // Si le cache contient un objet invalide (classe incomplète, mauvais type, etc.)
        if (! $cached instanceof ParametreGeneral) {
            $cached = ParametreGeneral::firstOrCreate(
                ['id' => 1],
                ['nom_plateforme' => 'Ebeb Finance']
            );

            Cache::put(self::CACHE_KEY, $cached, self::CACHE_TTL);
        }

        return $cached;
    }

    /** Retourne la valeur d'un champ texte (utile dans les vues/mails). */
    public static function get(string $champ, string $defaut = ''): string
    {
        $instance = self::getInstance();
        return (string) ($instance->{$champ} ?? $defaut);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Lecture
    // ─────────────────────────────────────────────────────────────────────────

    public function obtenir(): array
    {
        return ['success' => true, 'data' => self::getInstance()];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Mise à jour groupée (textes + fichiers)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * @param array<string,mixed>            $data    Champs texte validés
     * @param array<string,UploadedFile>     $fichiers Fichiers uploadés (clé = champ DB)
     * @param string|null                    $adminId
     */
    public function sauvegarder(array $data, array $fichiers, ?string $adminId = null): array
    {
        $parametres = ParametreGeneral::firstOrCreate(
            ['id' => 1],
            ['nom_plateforme' => 'Ebeb Finance']
        );

        // Traiter les fichiers
        foreach ($fichiers as $champ => $fichier) {
            // Supprimer l'ancien fichier si présent
            $ancien = $parametres->getRawOriginal($champ);
            if ($ancien && Storage::disk(self::DISK)->exists($ancien)) {
                Storage::disk(self::DISK)->delete($ancien);
            }
            $data[$champ] = $fichier->store(self::DIR, self::DISK);
        }

        if ($adminId) {
            $data['modifie_par'] = $adminId;
        }

        $parametres->update($data);
        Cache::forget(self::CACHE_KEY);

        return ['success' => true, 'data' => $parametres->fresh()];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Suppression d'un logo individuel
    // ─────────────────────────────────────────────────────────────────────────

    public function supprimerFichier(string $champ, ?string $adminId = null): array
    {
        if (!in_array($champ, ParametreGeneral::$CHAMPS_FICHIERS, true)) {
            return ['success' => false, 'message' => 'Champ fichier inconnu.'];
        }

        $parametres = self::getInstance();
        $chemin = $parametres->getRawOriginal($champ);

        if ($chemin && Storage::disk(self::DISK)->exists($chemin)) {
            Storage::disk(self::DISK)->delete($chemin);
        }

        $parametres->update([$champ => null, 'modifie_par' => $adminId]);
        Cache::forget(self::CACHE_KEY);

        return ['success' => true, 'message' => 'Fichier supprimé.', 'data' => $parametres->fresh()];
    }
}
