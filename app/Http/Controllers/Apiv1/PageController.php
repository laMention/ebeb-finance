<?php

namespace App\Http\Controllers\Apiv1;

use App\Http\Controllers\BaseController;
use App\Models\Page;

class PageController extends BaseController
{
    /**
     * Contenu publié d'une page CMS par type (`CGU`,
     * `POLITIQUE_CONFIDENTIALITE`, ...) — consultation depuis l'application
     * mobile (Conditions générales, Avis de confidentialité...).
     */
    public function parType(string $type)
    {
        $type = strtoupper($type);

        if (!array_key_exists($type, Page::$TYPES)) {
            return $this->sendError('Type de page inconnu.', [], 404);
        }

        $page = Page::where('type_page', $type)
            ->where('statut', 'PUBLIE')
            ->orderByDesc('publie_le')
            ->first();

        return $this->sendResponse(
            $page ? [
                'id'         => $page->id,
                'titre'      => $page->titre,
                'contenu'    => $page->contenu,
                'type_page'  => $page->type_page,
                'slug'       => $page->slug,
                'publie_le'  => $page->publie_le,
                'updated_at' => $page->updated_at,
            ] : null,
            $page
                ? 'Page récupérée avec succès.'
                : "Aucun contenu publié pour cette page."
        );
    }
}
