<?php

namespace App\Http\Controllers\Apiv1\Admin;

use App\Http\Controllers\BaseController;
use App\Models\AuditSecuriteRapport;
use App\Models\AuditSecuriteVulnerabilite;
use App\Services\SecurityAuditRunner;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Maatwebsite\Excel\Facades\Excel;

class SecurityAuditController extends BaseController
{
    public function dashboard(): JsonResponse
    {
        $rapport = AuditSecuriteRapport::with(['realisePar:id,nom,prenom'])
            ->orderByDesc('date_audit')
            ->first();

        if (!$rapport) {
            return $this->sendResponse([
                'rapport'          => null,
                'vulnerabilites'   => [],
                'stats'            => $this->emptyStats(),
                'historique_count' => 0,
            ], 'Aucun audit réalisé');
        }

        $stats = $this->buildStats($rapport);

        return $this->sendResponse([
            'rapport'          => $this->formatRapport($rapport),
            'stats'            => $stats,
            'historique_count' => AuditSecuriteRapport::count(),
        ], 'Tableau de bord sécurité');
    }

    public function vulnerabilites(Request $request): JsonResponse
    {
        $rapport = AuditSecuriteRapport::orderByDesc('date_audit')->first();

        if (!$rapport) {
            return $this->sendResponse(['data' => [], 'meta' => []], 'Aucun audit disponible');
        }

        $query = $rapport->vulnerabilites()->orderByRaw("FIELD(criticite,'CRITIQUE','ELEVE','MOYEN','FAIBLE','INFO')");

        if ($request->filled('criticite')) {
            $query->where('criticite', strtoupper($request->input('criticite')));
        }
        if ($request->filled('statut')) {
            $query->where('statut', strtoupper($request->input('statut')));
        }
        if ($request->filled('categorie')) {
            $query->where('categorie', 'like', '%' . $request->input('categorie') . '%');
        }

        $perPage = min((int) $request->input('per_page', 20), 100);
        $paginated = $query->paginate($perPage);

        return $this->sendResponse([
            'data' => $paginated->items(),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'per_page'     => $paginated->perPage(),
                'total'        => $paginated->total(),
            ],
        ], 'Liste des vulnérabilités');
    }

    public function historique(): JsonResponse
    {
        $rapports = AuditSecuriteRapport::with(['realisePar:id,nom,prenom'])
            ->orderByDesc('date_audit')
            ->get()
            ->map(fn($r) => $this->formatRapport($r));

        return $this->sendResponse($rapports, 'Historique des audits');
    }

    public function marquerCorrige(Request $request, AuditSecuriteVulnerabilite $vulnerabilite): JsonResponse
    {
        $request->validate([
            'notes_correction' => ['nullable', 'string', 'max:2000'],
        ]);

        $vulnerabilite->update([
            'statut'           => 'CORRIGE',
            'date_correction'  => now(),
            'corrige_par'      => auth('sanctum')->id(),
            'notes_correction' => $request->input('notes_correction'),
        ]);

        $vulnerabilite->rapport->recalculerCompteurs();

        return $this->sendResponse($vulnerabilite->fresh(), 'Vulnérabilité marquée comme corrigée');
    }

    public function changerStatut(Request $request, AuditSecuriteVulnerabilite $vulnerabilite): JsonResponse
    {
        $request->validate([
            'statut'           => ['required', 'in:DETECTE,EN_COURS,CORRIGE,ACCEPTE,FAUX_POSITIF'],
            'notes_correction' => ['nullable', 'string', 'max:2000'],
        ]);

        $data = ['statut' => $request->input('statut')];

        if ($request->input('statut') === 'CORRIGE') {
            $data['date_correction'] = now();
            $data['corrige_par']     = auth('sanctum')->id();
        }

        if ($request->filled('notes_correction')) {
            $data['notes_correction'] = $request->input('notes_correction');
        }

        $vulnerabilite->update($data);
        $vulnerabilite->rapport->recalculerCompteurs();

        return $this->sendResponse($vulnerabilite->fresh(), 'Statut mis à jour');
    }

    public function exportPdf(): Response
    {
        $rapport = AuditSecuriteRapport::with(['vulnerabilites', 'realisePar'])
            ->orderByDesc('date_audit')
            ->firstOrFail();

        $pdf = Pdf::loadView('exports.audit-securite-pdf', [
            'rapport'          => $rapport,
            'vulnerabilites'   => $rapport->vulnerabilites()->orderByRaw("FIELD(criticite,'CRITIQUE','ELEVE','MOYEN','FAIBLE','INFO')")->get(),
            'stats'            => $this->buildStats($rapport),
            'date_generation'  => now()->format('d/m/Y H:i'),
        ])->setPaper('a4', 'portrait');

        return $pdf->download('audit-securite-' . now()->format('Y-m-d') . '.pdf');
    }

    public function exportExcel(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $rapport = AuditSecuriteRapport::with(['vulnerabilites'])
            ->orderByDesc('date_audit')
            ->firstOrFail();

        $vulnerabilites = $rapport->vulnerabilites()
            ->orderByRaw("FIELD(criticite,'CRITIQUE','ELEVE','MOYEN','FAIBLE','INFO')")
            ->get();

        $rows = $vulnerabilites->map(fn($v) => [
            'Code'             => $v->code,
            'Titre'            => $v->titre,
            'Criticité'        => $v->criticite,
            'Statut'           => $v->statut,
            'Catégorie'        => $v->categorie,
            'Description'      => $v->description,
            'Impact'           => $v->impact,
            'Recommandation'   => $v->recommandation,
            'Fichier'          => $v->fichier,
            'Ligne'            => $v->ligne,
            'Détecté le'       => $v->date_detection?->format('d/m/Y'),
            'Corrigé le'       => $v->date_correction?->format('d/m/Y') ?? '',
            'Notes correction' => $v->notes_correction ?? '',
        ]);

        return Excel::download(
            new \App\Exports\AuditSecuriteExport($rows),
            'audit-securite-' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    private function buildStats(AuditSecuriteRapport $rapport): array
    {
        $total    = $rapport->nb_critique + $rapport->nb_eleve + $rapport->nb_moyen + $rapport->nb_faible + $rapport->nb_info;
        $corrige  = $rapport->nb_corrige;
        $restant  = $total - $corrige;

        return [
            'total'        => $total,
            'corrige'      => $corrige,
            'restant'      => $restant,
            'taux_correction' => $total > 0 ? round(($corrige / $total) * 100, 1) : 0,
            'par_criticite' => [
                'CRITIQUE' => $rapport->nb_critique,
                'ELEVE'    => $rapport->nb_eleve,
                'MOYEN'    => $rapport->nb_moyen,
                'FAIBLE'   => $rapport->nb_faible,
                'INFO'     => $rapport->nb_info,
            ],
            'par_statut'   => $rapport->vulnerabilites()
                ->selectRaw('statut, count(*) as total')
                ->groupBy('statut')
                ->pluck('total', 'statut'),
        ];
    }

    private function emptyStats(): array
    {
        return [
            'total' => 0, 'corrige' => 0, 'restant' => 0, 'taux_correction' => 0,
            'par_criticite' => ['CRITIQUE' => 0, 'ELEVE' => 0, 'MOYEN' => 0, 'FAIBLE' => 0, 'INFO' => 0],
            'par_statut'    => [],
        ];
    }

    // ─── Lancement de l'audit ──────────────────────────────────────────────

    public function lancer(Request $request): JsonResponse
    {
        // Verrou : un seul audit actif à la fois
        if (Cache::has('audit_securite_en_cours')) {
            return $this->sendError('Un audit est déjà en cours. Veuillez patienter.', [], 409);
        }

        if (AuditSecuriteRapport::where('est_en_cours', true)->exists()) {
            return $this->sendError('Un audit est déjà en cours d\'exécution.', [], 409);
        }

        // Créer le rapport en statut EN_COURS
        $admin   = auth('sanctum')->user();
        $rapport = AuditSecuriteRapport::create([
            'titre'       => 'Audit dynamique — ' . now()->format('d/m/Y H:i'),
            'version'     => '2.0',
            'statut'      => 'EN_COURS',
            'est_en_cours' => true,
            'notes'       => 'Audit automatique lancé par ' . ($admin ? $admin->prenom . ' ' . $admin->nom : 'Système'),
            'realise_par' => $admin?->id,
            'date_audit'  => now(),
        ]);

        Cache::put('audit_securite_en_cours', $rapport->id, now()->addMinutes(5));

        try {
            $runner   = new SecurityAuditRunner();
            $findings = $runner->run();

            // Supprimer les vulnérabilités précédentes de ce rapport (cas retry)
            $rapport->vulnerabilites()->delete();

            // Persister les vulnérabilités
            foreach ($findings as $f) {
                AuditSecuriteVulnerabilite::create([
                    'rapport_id'        => $rapport->id,
                    'code'              => $f['code'],
                    'titre'             => $f['titre'],
                    'criticite'         => $f['criticite'],
                    'statut'            => $f['statut'],
                    'categorie'         => $f['categorie'],
                    'description'       => $f['description'],
                    'impact'            => $f['impact'],
                    'recommandation'    => $f['recommandation'],
                    'fichier'           => $f['fichier'] ?? null,
                    'ligne'             => $f['ligne'] ?? null,
                    'correctable_auto'  => $f['correctable_auto'] ?? false,
                    'correction_label'  => $f['correction_label'] ?? null,
                    'correction_action' => $f['correction_action'] ?? null,
                    'date_detection'    => now(),
                ]);
            }

            // Mettre à jour les compteurs
            $rapport->recalculerCompteurs();
            $nb_correctables = AuditSecuriteVulnerabilite::where('rapport_id', $rapport->id)
                ->where('correctable_auto', true)
                ->where('statut', 'DETECTE')
                ->count();

            $rapport->update([
                'statut'           => 'TERMINE',
                'est_en_cours'     => false,
                'nb_correctables'  => $nb_correctables,
            ]);

            Cache::forget('audit_securite_en_cours');

            // Journaliser
            \Log::info('audit-securite: ' . count($findings) . ' vulnérabilité(s) détectée(s)', ['rapport_id' => $rapport->id]);

            $vulnerabilites = AuditSecuriteVulnerabilite::where('rapport_id', $rapport->id)
                ->orderByRaw("FIELD(criticite,'CRITIQUE','ELEVE','MOYEN','FAIBLE','INFO')")
                ->get();

            return $this->sendResponse([
                'rapport'          => $this->formatRapport($rapport->fresh()),
                'stats'            => $this->buildStats($rapport->fresh()),
                'vulnerabilites'   => $vulnerabilites,
                'nb_correctables'  => $nb_correctables,
            ], count($findings) . ' vulnérabilité(s) détectée(s). ' . $nb_correctables . ' correction(s) automatique(s) disponible(s).');

        } catch (\Throwable $th) {
            $rapport->update(['statut' => 'ARCHIVE', 'est_en_cours' => false]);
            Cache::forget('audit_securite_en_cours');
            return $this->throw($th);
        }
    }

    public function statut(): JsonResponse
    {
        $enCours = AuditSecuriteRapport::where('est_en_cours', true)->first();
        return $this->sendResponse([
            'en_cours'  => (bool) $enCours,
            'rapport_id' => $enCours?->id,
        ], 'Statut de l\'audit');
    }

    public function appliquerCorrections(Request $request): JsonResponse
    {
        $rapport = AuditSecuriteRapport::orderByDesc('date_audit')->first();

        if (!$rapport) {
            return $this->sendError('Aucun audit disponible.', [], 404);
        }

        $correctables = AuditSecuriteVulnerabilite::where('rapport_id', $rapport->id)
            ->where('correctable_auto', true)
            ->where('statut', 'DETECTE')
            ->get();

        if ($correctables->isEmpty()) {
            return $this->sendError('Aucune correction automatique disponible.', [], 422);
        }

        $appliquees = [];
        $echecs     = [];
        $admin      = auth('sanctum')->user();

        foreach ($correctables as $vuln) {
            $action = $vuln->correction_action ?? '';

            try {
                $ok = $this->appliquerAction($action, $vuln);

                if ($ok) {
                    $vuln->update([
                        'statut'           => 'CORRIGE',
                        'date_correction'  => now(),
                        'corrige_par'      => $admin?->id,
                        'notes_correction' => 'Correction automatique appliquée par le système.',
                    ]);
                    $appliquees[] = ['code' => $vuln->code, 'titre' => $vuln->titre, 'action' => $action];
                } else {
                    $echecs[] = ['code' => $vuln->code, 'titre' => $vuln->titre, 'raison' => 'Action non reconnue'];
                }
            } catch (\Throwable $th) {
                $echecs[] = ['code' => $vuln->code, 'titre' => $vuln->titre, 'raison' => $th->getMessage()];
            }
        }

        $rapport->recalculerCompteurs();
        $rapport->update(['nb_correctables' => $rapport->vulnerabilites()->where('correctable_auto', true)->where('statut', 'DETECTE')->count()]);

        \Log::info('audit-securite: ' . \count($appliquees) . ' correction(s) appliquée(s)', ['rapport_id' => $rapport->id]);

        return $this->sendResponse([
            'appliquees'  => $appliquees,
            'echecs'      => $echecs,
            'rapport'     => $this->formatRapport($rapport->fresh()),
            'stats'       => $this->buildStats($rapport->fresh()),
        ], count($appliquees) . ' correction(s) appliquée(s) avec succès.');
    }

    private function appliquerAction(string $action, AuditSecuriteVulnerabilite $vuln): bool
    {
        if (str_starts_with($action, 'set_env:')) {
            $pair  = substr($action, 8);
            [$key, $val] = explode('=', $pair, 2);
            return $this->setEnvValue(trim($key), trim($val));
        }

        if ($action === 'create_cors_config') {
            return $this->createCorsConfig();
        }

        return false;
    }

    private function setEnvValue(string $key, string $value): bool
    {
        $envPath = base_path('.env');
        if (!File::exists($envPath)) {
            return false;
        }

        $content = File::get($envPath);
        $pattern = "/^{$key}=.*$/m";

        if (preg_match($pattern, $content)) {
            $content = preg_replace($pattern, "{$key}={$value}", $content);
        } else {
            $content .= PHP_EOL . "{$key}={$value}";
        }

        File::put($envPath, $content);

        // Invalider le cache de config
        try {
            \Artisan::call('config:clear');
        } catch (\Throwable) {
        }

        return true;
    }

    private function createCorsConfig(): bool
    {
        $corsPath = config_path('cors.php');
        if (File::exists($corsPath)) {
            return true;
        }

        $content = <<<'PHP'
<?php

return [
    'paths'                    => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods'          => ['*'],
    'allowed_origins'          => explode(',', env('CORS_ALLOWED_ORIGINS', 'http://localhost:3000,http://localhost:5173')),
    'allowed_origins_patterns' => [],
    'allowed_headers'          => ['Content-Type', 'X-Requested-With', 'Authorization', 'Accept', 'X-Webhook-Secret'],
    'exposed_headers'          => [],
    'max_age'                  => 0,
    'supports_credentials'     => true,
];
PHP;

        File::put($corsPath, $content);
        return true;
    }

    private function formatRapport(AuditSecuriteRapport $rapport): array
    {
        return [
            'id'               => $rapport->id,
            'titre'            => $rapport->titre,
            'version'          => $rapport->version,
            'statut'           => $rapport->statut,
            'est_en_cours'     => (bool) $rapport->est_en_cours,
            'date_audit'       => $rapport->date_audit?->format('d/m/Y H:i'),
            'realise_par'      => $rapport->realisePar
                ? $rapport->realisePar->prenom . ' ' . $rapport->realisePar->nom
                : 'Système',
            'nb_critique'      => $rapport->nb_critique,
            'nb_eleve'         => $rapport->nb_eleve,
            'nb_moyen'         => $rapport->nb_moyen,
            'nb_faible'        => $rapport->nb_faible,
            'nb_info'          => $rapport->nb_info,
            'nb_corrige'       => $rapport->nb_corrige,
            'nb_correctables'  => $rapport->nb_correctables,
            'notes'            => $rapport->notes,
        ];
    }
}
