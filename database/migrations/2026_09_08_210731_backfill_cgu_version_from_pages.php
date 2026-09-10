<?php

use App\Models\CguVersion;
use App\Models\Page;
use Illuminate\Database\Migrations\Migration;

/**
 * Migration de données : si une page CGU publiée existe déjà, en crée la
 * première `CguVersion` — sinon la modale d'acceptation à l'inscription
 * n'aurait aucun contenu tant qu'un admin n'aurait pas republié la page.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (CguVersion::exists()) {
            return;
        }

        $page = Page::where('type_page', 'CGU')->where('statut', 'PUBLIE')->first();

        if (!$page) {
            return;
        }

        CguVersion::create([
            'page_id'        => $page->id,
            'numero_version' => 1,
            'titre'          => $page->titre,
            'contenu'        => $page->contenu,
            'est_active'     => true,
            'publie_le'      => $page->publie_le ?? now(),
        ]);
    }

    public function down(): void
    {
        CguVersion::where('numero_version', 1)->delete();
    }
};
