<?php

namespace App\Services;

use App\Models\Administrateur;
use App\Models\Alerte;
use App\Models\CompteMobileMoney;
use App\Models\Cotisation;
use App\Models\MoyenPaiement;
use App\Models\ObjectifEpargne;
use App\Models\Operation;
use App\Models\PartenairesFinancier;
use App\Models\Reversement;
use App\Models\TypeCotisation;
use App\Models\User;
use Carbon\Carbon;

class ExportService
{
    private const MAX_ROWS = 10000;

    /**
     * Retourne ['titre', 'headings', 'rows'] pour le module donné.
     */
    public function exporter(string $module, array $params): array
    {
        // Journalisation de chaque export (SEC-018 — traçabilité admin)
        \Log::info('export-admin', [
            'module'   => $module,
            'admin_id' => request()->user()?->id,
            'ip'       => request()->ip(),
            'filtres'  => array_keys(array_filter($params)),
        ]);

        return match ($module) {
            'utilisateurs'    => $this->utilisateurs($params),
            'transactions'    => $this->transactions($params),
            'repartitions'    => $this->repartitions($params),
            'reversements'    => $this->reversements($params),
            'cotisations'     => $this->cotisations($params),
            'epargne'         => $this->epargne($params),
            'mobile-money'    => $this->mobileMoney($params),
            'partenaires'     => $this->partenaires($params),
            'moyens-paiement' => $this->moyensPaiement($params),
            'types-cotisation'=> $this->typesCotisation($params),
            'administrateurs' => $this->administrateurs($params),
            'alertes'         => $this->alertes($params),
            default           => throw new \InvalidArgumentException("Module inconnu : {$module}"),
        };
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Utilisateurs
    // ─────────────────────────────────────────────────────────────────────────

    private function utilisateurs(array $p): array
    {
        $q = User::query();

        if (!empty($p['recherche'])) {
            $r = $p['recherche'];
            $q->where(fn($w) => $w
                ->where('nom', 'like', "%{$r}%")
                ->orWhere('prenom', 'like', "%{$r}%")
                ->orWhere('telephone', 'like', "%{$r}%")
                ->orWhere('reference', 'like', "%{$r}%")
                ->orWhere('email', 'like', "%{$r}%")
            );
        }
        if (!empty($p['statut']))     $q->where('statut', $p['statut']);
        if (!empty($p['type_carte'])) $q->where('type_carte', $p['type_carte']);
        if (!empty($p['sexe']))       $q->where('sexe', $p['sexe']);
        if (!empty($p['ville']))      $q->where('ville', 'like', "%{$p['ville']}%");
        if (!empty($p['profession'])) $q->where('profession', 'like', "%{$p['profession']}%");
        if (!empty($p['date_debut'])) $q->whereDate('created_at', '>=', $p['date_debut']);
        if (!empty($p['date_fin']))   $q->whereDate('created_at', '<=', $p['date_fin']);

        $rows = $q->orderByDesc('created_at')->limit(self::MAX_ROWS)->get();

        return [
            'titre'    => 'Utilisateurs',
            'headings' => ['Nom', 'Prénom', 'Téléphone', 'Email', 'Référence', 'Statut', 'Type carte', 'Sexe', 'Ville', 'Profession', 'Inscrit le'],
            'rows'     => $rows->map(fn($u) => [
                $u->nom, $u->prenom, $u->telephone, $u->email ?? '',
                $u->reference ?? '', $u->statut ?? '', $u->type_carte ?? '',
                $u->sexe ?? '', $u->ville ?? '', $u->profession ?? '',
                $u->created_at?->format('d/m/Y'),
            ])->toArray(),
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Transactions
    // ─────────────────────────────────────────────────────────────────────────

    private function transactions(array $p): array
    {
        $q = Operation::with([
            'user:id,nom,prenom,telephone',
            'paiement_entrant:id,operateur_source',
        ])->orderByDesc('date_operation');

        if (!empty($p['recherche'])) {
            $r = $p['recherche'];
            $q->where(fn($w) => $w->where('reference', 'like', "%{$r}%")
                ->orWhereHas('user', fn($u) => $u
                    ->where('nom', 'like', "%{$r}%")
                    ->orWhere('prenom', 'like', "%{$r}%")
                    ->orWhere('telephone', 'like', "%{$r}%")
                )
            );
        }
        if (!empty($p['type_operation'])) $q->where('type_operation', $p['type_operation']);
        if (!empty($p['statut']))         $q->where('statut', $p['statut']);
        if (!empty($p['operateur']))      $q->whereHas('paiement_entrant', fn($w) => $w->where('operateur_source', $p['operateur']));
        if (!empty($p['date_debut']))     $q->whereDate('date_operation', '>=', $p['date_debut']);
        if (!empty($p['date_fin']))       $q->whereDate('date_operation', '<=', $p['date_fin']);

        $rows = $q->limit(self::MAX_ROWS)->get();

        return [
            'titre'    => 'Transactions',
            'headings' => ['Référence', 'Type', 'Libellé', 'Montant (FCFA)', 'Sens', 'Statut', 'Opérateur', 'Utilisateur', 'Téléphone', 'Date'],
            'rows'     => $rows->map(fn($o) => [
                $o->reference ?? '', $o->type_operation ?? '', $o->libelle ?? '',
                number_format((float) $o->montant, 0, ',', ' '),
                $o->sens ?? '', $o->statut ?? '',
                $o->paiement_entrant?->operateur_source ?? '',
                $o->user ? "{$o->user->prenom} {$o->user->nom}" : '',
                $o->user?->telephone ?? '',
                $o->date_operation ? Carbon::parse($o->date_operation)->format('d/m/Y H:i') : '',
            ])->toArray(),
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Répartitions
    // ─────────────────────────────────────────────────────────────────────────

    private function repartitions(array $p): array
    {
        $q = Operation::with([
            'user:id,nom,prenom,telephone',
            'paiement_entrant:id,montant_brut,operateur_source',
        ])
        ->whereNull('operation_parent_id')
        ->whereNotNull('paiement_entrant_id')
        ->withSum('sous_operations', 'montant')
        ->withCount('sous_operations');

        if (!empty($p['search'])) {
            $s = $p['search'];
            $q->where(fn($w) => $w->where('reference', 'like', "%{$s}%")
                ->orWhereHas('user', fn($u) => $u
                    ->where('nom', 'like', "%{$s}%")
                    ->orWhere('prenom', 'like', "%{$s}%")
                    ->orWhere('telephone', 'like', "%{$s}%")
                )
            );
        }
        if (!empty($p['statut']))     $q->where('statut', $p['statut']);
        if (!empty($p['date_debut'])) $q->whereDate('date_operation', '>=', $p['date_debut']);
        if (!empty($p['date_fin']))   $q->whereDate('date_operation', '<=', $p['date_fin']);

        $rows = $q->orderByDesc('date_operation')->limit(self::MAX_ROWS)->get();

        return [
            'titre'    => 'Répartitions',
            'headings' => ['Référence', 'Montant reçu (FCFA)', 'Montant réparti (FCFA)', 'Nb sous-ops', 'Statut', 'Opérateur', 'Utilisateur', 'Téléphone', 'Date'],
            'rows'     => $rows->map(fn($o) => [
                $o->reference ?? '',
                number_format((float) ($o->paiement_entrant?->montant_brut ?? 0), 0, ',', ' '),
                number_format((float) ($o->sous_operations_sum_montant ?? 0), 0, ',', ' '),
                $o->sous_operations_count ?? 0,
                $o->statut ?? '',
                $o->paiement_entrant?->operateur_source ?? '',
                $o->user ? "{$o->user->prenom} {$o->user->nom}" : '',
                $o->user?->telephone ?? '',
                $o->date_operation ? Carbon::parse($o->date_operation)->format('d/m/Y H:i') : '',
            ])->toArray(),
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Reversements
    // ─────────────────────────────────────────────────────────────────────────

    private function reversements(array $p): array
    {
        $q = Reversement::with(['partenaire:id,nom,type']);

        if (!empty($p['search'])) {
            $s = $p['search'];
            $q->where(fn($w) => $w->where('reference', 'like', "%{$s}%")
                ->orWhereHas('partenaire', fn($pt) => $pt->where('nom', 'like', "%{$s}%"))
            );
        }
        if (!empty($p['partenaire_id'])) $q->where('partenaires_financier_id', $p['partenaire_id']);
        if (!empty($p['statut']))         $q->where('statut', $p['statut']);
        if (!empty($p['date_debut']))     $q->whereDate('created_at', '>=', $p['date_debut']);
        if (!empty($p['date_fin']))       $q->whereDate('created_at', '<=', $p['date_fin']);

        $rows = $q->orderByDesc('created_at')->limit(self::MAX_ROWS)->get();

        return [
            'titre'    => 'Reversements',
            'headings' => ['Référence', 'Partenaire', 'Type partenaire', 'Montant total (FCFA)', 'Statut', 'Date reversement', 'Créé le'],
            'rows'     => $rows->map(fn($r) => [
                $r->reference ?? '',
                $r->partenaire?->nom ?? '',
                $r->partenaire?->type ?? '',
                number_format((float) $r->montant_total, 0, ',', ' '),
                $r->statut ?? '',
                $r->date_reversement ? Carbon::parse($r->date_reversement)->format('d/m/Y') : '',
                $r->created_at?->format('d/m/Y'),
            ])->toArray(),
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Cotisations
    // ─────────────────────────────────────────────────────────────────────────

    private function cotisations(array $p): array
    {
        $q = Cotisation::with([
            'user:id,nom,prenom,telephone',
            'typeCotisation:id,libelle,code,categorie',
        ])->orderByDesc('annee')->orderByDesc('mois');

        if (!empty($p['recherche'])) {
            $r = $p['recherche'];
            $q->whereHas('user', fn($u) => $u
                ->where('nom', 'like', "%{$r}%")
                ->orWhere('prenom', 'like', "%{$r}%")
                ->orWhere('telephone', 'like', "%{$r}%")
            );
        }
        if (!empty($p['statut'])) $q->where('statut', $p['statut']);
        if (!empty($p['annee']))  $q->where('annee', (int) $p['annee']);
        if (!empty($p['mois']))   $q->where('mois', (int) $p['mois']);

        $rows = $q->limit(self::MAX_ROWS)->get();

        return [
            'titre'    => 'Cotisations',
            'headings' => ['Utilisateur', 'Téléphone', 'Type', 'Code', 'Catégorie', 'Année', 'Mois', 'Statut', 'Montant versé (FCFA)'],
            'rows'     => $rows->map(fn($c) => [
                $c->user ? "{$c->user->prenom} {$c->user->nom}" : '',
                $c->user?->telephone ?? '',
                $c->typeCotisation?->libelle ?? '',
                $c->typeCotisation?->code ?? '',
                $c->typeCotisation?->categorie ?? '',
                $c->annee ?? '', $c->mois ?? '',
                $c->statut ?? '',
                number_format((float) ($c->montant_verse ?? 0), 0, ',', ' '),
            ])->toArray(),
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Épargne
    // ─────────────────────────────────────────────────────────────────────────

    private function epargne(array $p): array
    {
        $q = ObjectifEpargne::with(['user:id,nom,prenom,telephone']);

        if (!empty($p['recherche'])) {
            $r = $p['recherche'];
            $q->where(fn($w) => $w->where('libelle', 'like', "%{$r}%")
                ->orWhereHas('user', fn($u) => $u
                    ->where('nom', 'like', "%{$r}%")
                    ->orWhere('prenom', 'like', "%{$r}%")
                    ->orWhere('telephone', 'like', "%{$r}%")
                )
            );
        }
        if (!empty($p['statut'])) {
            $today = now()->toDateString();
            match ($p['statut']) {
                'OBJECTIF_ATTEINT' => $q->whereRaw('montant_epargne >= montant_cible'),
                'EN_RETARD'        => $q->whereRaw('montant_epargne < montant_cible')->whereNotNull('date_limite')->whereDate('date_limite', '<', $today),
                'EN_COURS'         => $q->whereRaw('montant_epargne < montant_cible')->where(fn($w) => $w->whereNull('date_limite')->orWhereDate('date_limite', '>=', $today)),
                default            => null,
            };
        }
        if (isset($p['est_actif']) && $p['est_actif'] !== '') {
            $q->where('est_actif', filter_var($p['est_actif'], FILTER_VALIDATE_BOOLEAN));
        }

        $rows = $q->orderByDesc('montant_epargne')->limit(self::MAX_ROWS)->get();

        return [
            'titre'    => 'Épargne',
            'headings' => ['Libellé', 'Utilisateur', 'Téléphone', 'Montant épargne (FCFA)', 'Montant cible (FCFA)', 'Progression (%)', 'Date limite', 'Actif', 'Créé le'],
            'rows'     => $rows->map(fn($o) => [
                $o->libelle,
                $o->user ? "{$o->user->prenom} {$o->user->nom}" : '',
                $o->user?->telephone ?? '',
                number_format((float) ($o->montant_epargne ?? 0), 0, ',', ' '),
                number_format((float) ($o->montant_cible ?? 0), 0, ',', ' '),
                $o->montant_cible > 0
                    ? round(($o->montant_epargne / $o->montant_cible) * 100, 1) . '%'
                    : '0%',
                $o->date_limite ?? '',
                $o->est_actif ? 'Oui' : 'Non',
                $o->created_at?->format('d/m/Y'),
            ])->toArray(),
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Mobile Money
    // ─────────────────────────────────────────────────────────────────────────

    private function mobileMoney(array $p): array
    {
        $q = CompteMobileMoney::with(['user:id,nom,prenom,telephone']);

        if (!empty($p['search'])) {
            $s = $p['search'];
            $q->where(fn($w) => $w->where('numero_compte', 'like', "%{$s}%")
                ->orWhereHas('user', fn($u) => $u
                    ->where('nom', 'like', "%{$s}%")
                    ->orWhere('prenom', 'like', "%{$s}%")
                    ->orWhere('telephone', 'like', "%{$s}%")
                )
            );
        }
        if (!empty($p['operateur'])) $q->where('operateur', $p['operateur']);
        if (!empty($p['statut']))    $q->where('statut', $p['statut']);
        if (isset($p['est_principal']) && $p['est_principal'] !== '') {
            $q->where('est_principal', filter_var($p['est_principal'], FILTER_VALIDATE_BOOLEAN));
        }
        if (!empty($p['date_debut'])) $q->whereDate('created_at', '>=', $p['date_debut']);
        if (!empty($p['date_fin']))   $q->whereDate('created_at', '<=', $p['date_fin']);

        $rows = $q->latest()->limit(self::MAX_ROWS)->get();

        return [
            'titre'    => 'Comptes Mobile Money',
            'headings' => ['Numéro de compte', 'Opérateur', 'Utilisateur', 'Téléphone', 'Statut', 'Principal', 'Créé le'],
            'rows'     => $rows->map(fn($c) => [
                $c->numero_compte ?? '', $c->operateur ?? '',
                $c->user ? "{$c->user->prenom} {$c->user->nom}" : '',
                $c->user?->telephone ?? '',
                $c->statut ?? '', $c->est_principal ? 'Oui' : 'Non',
                $c->created_at?->format('d/m/Y'),
            ])->toArray(),
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Partenaires financiers
    // ─────────────────────────────────────────────────────────────────────────

    private function partenaires(array $p): array
    {
        $q = PartenairesFinancier::withCount('reversements');

        if (!empty($p['recherche'])) {
            $r = $p['recherche'];
            $q->where(fn($w) => $w->where('nom', 'like', "%{$r}%")->orWhere('code', 'like', "%{$r}%"));
        }
        if (!empty($p['type'])) $q->where('type', $p['type']);

        $rows = $q->orderBy('nom')->limit(self::MAX_ROWS)->get();

        return [
            'titre'    => 'Partenaires financiers',
            'headings' => ['Nom', 'Code', 'Type', 'Nb reversements', 'Créé le'],
            'rows'     => $rows->map(fn($pt) => [
                $pt->nom, $pt->code ?? '', $pt->type ?? '',
                $pt->reversements_count,
                $pt->created_at?->format('d/m/Y'),
            ])->toArray(),
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Moyens de paiement
    // ─────────────────────────────────────────────────────────────────────────

    private function moyensPaiement(array $p): array
    {
        $q = MoyenPaiement::query();

        if (!empty($p['search'])) {
            $s = $p['search'];
            $q->where(fn($w) => $w->where('libelle', 'like', "%{$s}%")->orWhere('operateur', 'like', "%{$s}%"));
        }

        $rows = $q->orderBy('libelle')->limit(self::MAX_ROWS)->get();

        return [
            'titre'    => 'Moyens de paiement',
            'headings' => ['Libellé', 'Opérateur', 'Statut', 'Par défaut', 'Créé le'],
            'rows'     => $rows->map(fn($m) => [
                $m->libelle, $m->operateur ?? '', $m->statut ?? '',
                $m->est_par_defaut ? 'Oui' : 'Non',
                $m->created_at?->format('d/m/Y'),
            ])->toArray(),
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Types de cotisation
    // ─────────────────────────────────────────────────────────────────────────

    private function typesCotisation(array $p): array
    {
        $q = TypeCotisation::query();

        if (!empty($p['search'])) {
            $s = $p['search'];
            $q->where(fn($w) => $w->where('libelle', 'like', "%{$s}%")->orWhere('code', 'like', "%{$s}%"));
        }

        $rows = $q->orderBy('libelle')->limit(self::MAX_ROWS)->get();

        return [
            'titre'    => 'Types de cotisation',
            'headings' => ['Libellé', 'Code', 'Catégorie', 'Montant (FCFA)', 'Obligatoire', 'Actif', 'Créé le'],
            'rows'     => $rows->map(fn($t) => [
                $t->libelle, $t->code ?? '', $t->categorie ?? '',
                number_format((float) ($t->montant ?? 0), 0, ',', ' '),
                isset($t->est_obligatoire) ? ($t->est_obligatoire ? 'Oui' : 'Non') : '',
                isset($t->est_actif) ? ($t->est_actif ? 'Oui' : 'Non') : '',
                $t->created_at?->format('d/m/Y'),
            ])->toArray(),
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Administrateurs
    // ─────────────────────────────────────────────────────────────────────────

    private function administrateurs(array $p): array
    {
        $q = Administrateur::with(['roles']);

        if (!empty($p['avec_archives'])) {
            $q->withTrashed();
        } elseif (!empty($p['seulement_archives'])) {
            $q->onlyTrashed();
        }

        if (!empty($p['search'])) {
            $s = $p['search'];
            $q->where(fn($w) => $w
                ->where('nom', 'like', "%{$s}%")
                ->orWhere('prenom', 'like', "%{$s}%")
                ->orWhere('email', 'like', "%{$s}%")
                ->orWhere('telephone', 'like', "%{$s}%")
            );
        }
        if (!empty($p['statut'])) $q->where('statut_compte', $p['statut']);
        if (!empty($p['role']))   $q->whereHas('roles', fn($r) => $r->where('name', $p['role']));

        $rows = $q->orderBy('nom')->limit(self::MAX_ROWS)->get();

        return [
            'titre'    => 'Administrateurs',
            'headings' => ['Nom', 'Prénom', 'Email', 'Téléphone', 'Statut', 'Rôles', 'Archivé', 'Créé le'],
            'rows'     => $rows->map(fn($a) => [
                $a->nom, $a->prenom, $a->email ?? '', $a->telephone ?? '',
                $a->statut_compte ?? '',
                $a->roles->pluck('name')->implode(', '),
                $a->deleted_at ? 'Oui' : 'Non',
                $a->created_at?->format('d/m/Y'),
            ])->toArray(),
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Alertes
    // ─────────────────────────────────────────────────────────────────────────

    private function alertes(array $p): array
    {
        $q = Alerte::query();

        if (!empty($p['avec_archives'])) {
            $q->withTrashed();
        } elseif (!empty($p['seulement_archives'])) {
            $q->onlyTrashed();
        }

        if (!empty($p['search'])) {
            $s = $p['search'];
            $q->where(fn($w) => $w
                ->where('titre', 'like', "%{$s}%")
                ->orWhere('description', 'like', "%{$s}%")
            );
        }
        if (!empty($p['niveau']))      $q->where('niveau', strtoupper($p['niveau']));
        if (!empty($p['type_alerte'])) $q->where('type_alerte', strtoupper($p['type_alerte']));
        if (isset($p['est_lu']) && $p['est_lu'] !== '') {
            $q->where('est_lu', (bool) $p['est_lu']);
        }
        if (!empty($p['date_debut']))  $q->whereDate('created_at', '>=', $p['date_debut']);
        if (!empty($p['date_fin']))    $q->whereDate('created_at', '<=', $p['date_fin']);

        $rows = $q->orderByDesc('created_at')->limit(self::MAX_ROWS)->get();

        return [
            'titre'    => 'Alertes',
            'headings' => ['Titre', 'Description', 'Niveau', 'Type', 'Lu', 'Lien', 'Date'],
            'rows'     => $rows->map(fn($a) => [
                $a->titre, $a->description ?? '', $a->niveau ?? '',
                $a->type_alerte ?? '',
                $a->est_lu ? 'Oui' : 'Non',
                $a->lien ?? '',
                $a->created_at?->format('d/m/Y H:i'),
            ])->toArray(),
        ];
    }
}
