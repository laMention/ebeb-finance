<?php

namespace App\Services;

use App\Models\Administrateur;
use App\Models\Page;

class PageService
{
    public static function lister(array $params): array
    {
        $perPage = min((int) ($params['per_page'] ?? 20), 100);
        $page    = (int) ($params['page'] ?? 1);

        $query = Page::query()
            ->with(['createur:id,nom,prenom', 'modificateur:id,nom,prenom']);

        if (!empty($params['search'])) {
            $q = '%' . $params['search'] . '%';
            $query->where(fn ($q2) => $q2->where('titre', 'like', $q)->orWhere('slug', 'like', $q));
        }

        if (!empty($params['statut'])) {
            $query->where('statut', strtoupper($params['statut']));
        }

        if (!empty($params['type_page'])) {
            $query->where('type_page', strtoupper($params['type_page']));
        }

        if (isset($params['archive']) && $params['archive'] === 'true') {
            $query->onlyTrashed();
        }

        $query->orderBy('ordre')->orderBy('created_at', 'desc');

        $paginated = $query->paginate($perPage, ['*'], 'page', $page);

        return [
            'success' => true,
            'data'    => $paginated->items(),
            'meta'    => [
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'per_page'     => $paginated->perPage(),
                'total'        => $paginated->total(),
            ],
        ];
    }

    public static function creer(array $data, Administrateur $admin): array
    {
        $data['cree_par']    = $admin->id;
        $data['modifie_par'] = $admin->id;
        $data['slug']        = Page::genererSlug($data['titre']);
        $data['statut']      = $data['statut'] ?? 'BROUILLON';

        if (($data['statut'] === 'PUBLIE') && empty($data['publie_le'])) {
            $data['publie_le'] = now();
        }

        $page = Page::create($data);

        AuditLogger::log('CREATE', $admin, 'pages', $page->id, null, $page->toArray());

        return ['success' => true, 'data' => $page->load(['createur', 'modificateur'])];
    }

    public static function afficher(string $id): array
    {
        $page = Page::withTrashed()->with(['createur:id,nom,prenom', 'modificateur:id,nom,prenom'])->findOrFail($id);
        return ['success' => true, 'data' => $page];
    }

    public static function modifier(string $id, array $data, Administrateur $admin): array
    {
        $page  = Page::findOrFail($id);
        $avant = $page->toArray();

        $data['modifie_par'] = $admin->id;

        if (!empty($data['titre']) && $data['titre'] !== $page->titre && empty($data['slug'])) {
            $data['slug'] = Page::genererSlug($data['titre'], $id);
        }

        if (!empty($data['statut']) && $data['statut'] === 'PUBLIE' && $page->statut !== 'PUBLIE') {
            $data['publie_le'] = $data['publie_le'] ?? now();
        }

        $page->update($data);

        AuditLogger::log('UPDATE', $admin, 'pages', $page->id, $avant, $page->fresh()->toArray());

        return ['success' => true, 'data' => $page->load(['createur', 'modificateur'])];
    }

    public static function publier(string $id, Administrateur $admin): array
    {
        $page  = Page::findOrFail($id);
        $avant = $page->toArray();

        $page->update([
            'statut'      => 'PUBLIE',
            'publie_le'   => $page->publie_le ?? now(),
            'modifie_par' => $admin->id,
        ]);

        AuditLogger::log('UPDATE', $admin, 'pages', $page->id, $avant, ['statut' => 'PUBLIE']);

        return ['success' => true, 'data' => $page];
    }

    public static function depublier(string $id, Administrateur $admin): array
    {
        $page  = Page::findOrFail($id);
        $avant = $page->toArray();

        $page->update(['statut' => 'BROUILLON', 'modifie_par' => $admin->id]);

        AuditLogger::log('UPDATE', $admin, 'pages', $page->id, $avant, ['statut' => 'BROUILLON']);

        return ['success' => true, 'data' => $page];
    }

    public static function archiver(string $id, Administrateur $admin): array
    {
        $page  = Page::findOrFail($id);
        $avant = $page->toArray();

        $page->delete();

        AuditLogger::log('DELETE', $admin, 'pages', $page->id, $avant, null);

        return ['success' => true, 'message' => 'Page archivée.'];
    }

    public static function restaurer(string $id, Administrateur $admin): array
    {
        $page = Page::onlyTrashed()->findOrFail($id);
        $page->restore();
        $page->update(['modifie_par' => $admin->id]);

        AuditLogger::log('RESTORE', $admin, 'pages', $page->id, null, $page->toArray());

        return ['success' => true, 'data' => $page];
    }

    public static function types(): array
    {
        return ['success' => true, 'data' => Page::$TYPES];
    }
}
