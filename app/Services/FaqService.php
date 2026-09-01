<?php

namespace App\Services;

use App\Models\Administrateur;
use App\Models\Faq;

class FaqService
{
    public function lister(array $params): array
    {
        $query = Faq::query()->with(['createur:id,nom,prenom', 'modificateur:id,nom,prenom']);

        if (!empty($params['search'])) {
            $q = '%' . $params['search'] . '%';
            $query->where(fn ($q2) => $q2->where('question', 'like', $q)->orWhere('reponse', 'like', $q));
        }

        if (!empty($params['statut'])) {
            $query->where('statut', strtoupper($params['statut']));
        }

        if (!empty($params['categorie'])) {
            $query->where('categorie', $params['categorie']);
        }

        if (isset($params['archive']) && $params['archive'] === 'true') {
            $query->onlyTrashed();
        }

        $perPage   = min((int) ($params['per_page'] ?? 20), 100);
        $page      = (int) ($params['page'] ?? 1);
        $paginated = $query->orderBy('ordre')->orderByDesc('created_at')->paginate($perPage, ['*'], 'page', $page);

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

    public function creer(array $data, Administrateur $admin): array
    {
        $data['cree_par']    = $admin->id;
        $data['modifie_par'] = $admin->id;
        $data['statut']      = $data['statut'] ?? 'BROUILLON';

        $faq = Faq::create($data);

        AuditLogger::log('CREATE', $admin, 'faqs', $faq->id, null, $faq->toArray());

        return ['success' => true, 'data' => $faq->load(['createur', 'modificateur'])];
    }

    public function afficher(string $id): array
    {
        $faq = Faq::withTrashed()->with(['createur:id,nom,prenom', 'modificateur:id,nom,prenom'])->findOrFail($id);
        return ['success' => true, 'data' => $faq];
    }

    public function modifier(string $id, array $data, Administrateur $admin): array
    {
        $faq   = Faq::findOrFail($id);
        $avant = $faq->toArray();

        $data['modifie_par'] = $admin->id;
        $faq->update($data);

        AuditLogger::log('UPDATE', $admin, 'faqs', $faq->id, $avant, $faq->fresh()->toArray());

        return ['success' => true, 'data' => $faq->load(['createur', 'modificateur'])];
    }

    public function publier(string $id, Administrateur $admin): array
    {
        $faq = Faq::findOrFail($id);
        $faq->update(['statut' => 'PUBLIE', 'modifie_par' => $admin->id]);

        AuditLogger::log('UPDATE', $admin, 'faqs', $faq->id, ['statut' => 'BROUILLON'], ['statut' => 'PUBLIE']);

        return ['success' => true, 'data' => $faq];
    }

    public function depublier(string $id, Administrateur $admin): array
    {
        $faq = Faq::findOrFail($id);
        $faq->update(['statut' => 'BROUILLON', 'modifie_par' => $admin->id]);

        AuditLogger::log('UPDATE', $admin, 'faqs', $faq->id, ['statut' => 'PUBLIE'], ['statut' => 'BROUILLON']);

        return ['success' => true, 'data' => $faq];
    }

    public function supprimer(string $id, Administrateur $admin): array
    {
        $faq   = Faq::findOrFail($id);
        $avant = $faq->toArray();
        $faq->delete();

        AuditLogger::log('DELETE', $admin, 'faqs', $faq->id, $avant, null);

        return ['success' => true, 'message' => 'FAQ supprimée.'];
    }
}
