<?php

namespace App\Services;

use App\Models\CguAcceptation;
use App\Models\CguVersion;
use App\Models\Page;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CguService
{
    public function versionActive(): ?CguVersion
    {
        return CguVersion::where('est_active', true)->latest('publie_le')->first();
    }

    public function enregistrerAcceptation(string $cguVersionId, ?string $ip, ?string $userAgent): CguAcceptation
    {
        return CguAcceptation::create([
            'cgu_version_id' => $cguVersionId,
            'user_id'        => null,
            'accepte_le'     => now(),
            'ip_adresse'     => $ip,
            'user_agent'     => $userAgent,
        ]);
    }

    /**
     * Rattache une acceptation faite avant la création du compte au compte
     * fraîchement créé. Un échec ici (acceptation introuvable ou déjà
     * rattachée) est journalisé mais ne doit jamais bloquer l'inscription :
     * le consentement a déjà été démontré côté client au moment de la
     * modale, un souci de rattachement est un problème d'intégrité mineur,
     * pas un motif de refuser un compte à un utilisateur réel.
     */
    public function rattacherAUtilisateur(string $acceptationId, User $user): void
    {
        $affectees = CguAcceptation::where('id', $acceptationId)
            ->whereNull('user_id')
            ->update(['user_id' => $user->id]);

        if ($affectees === 0) {
            \Log::warning('Acceptation CGU non rattachée (introuvable ou déjà liée)', [
                'acceptation_id' => $acceptationId,
                'user_id'        => $user->id,
            ]);
        }
    }

    /**
     * Synchronise le catalogue de versions CGU avec le contenu d'une page
     * `type_page = 'CGU'` fraîchement publiée. N'agit que sur ce type de
     * page et uniquement quand elle est publiée — n'affecte aucun autre
     * type de page CMS. Ne crée une nouvelle version que si le contenu a
     * réellement changé (évite les doublons sur une republication sans
     * modification).
     */
    public function synchroniserVersionCgu(Page $page): void
    {
        if ($page->type_page !== 'CGU' || $page->statut !== 'PUBLIE') {
            return;
        }

        $actuelle = $this->versionActive();

        if ($actuelle && $actuelle->titre === $page->titre && $actuelle->contenu === $page->contenu) {
            return;
        }

        DB::transaction(function () use ($page, $actuelle) {
            if ($actuelle) {
                $actuelle->update(['est_active' => false]);
            }

            $numeroVersion = (CguVersion::max('numero_version') ?? 0) + 1;

            CguVersion::create([
                'page_id'        => $page->id,
                'numero_version' => $numeroVersion,
                'titre'          => $page->titre,
                'contenu'        => $page->contenu,
                'est_active'     => true,
                'publie_le'      => now(),
            ]);
        });
    }
}
