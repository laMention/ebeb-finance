<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DocumentKYC;
use App\Models\LogAudit;
use App\Models\Operation;
use App\Models\ParametreGlobal;
use App\Models\PaiementEntrant;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AdminPanelController extends Controller
{
    public function dashboard(): Response
    {
        return Inertia::render('admin/dashboard', [
            'stats' => [
                'users' => User::count(),
                'kycPending' => DocumentKYC::where('statut', 'EN_ATTENTE')->count(),
                'operationsToday' => Operation::whereDate('created_at', today())->count(),
                'paiementsPending' => PaiementEntrant::where('statut', 'EN_ATTENTE')->count(),
            ],
        ]);
    }

    public function kyc(): Response
    {
        return Inertia::render('admin/kyc/index', [
            'items' => DocumentKYC::query()
                ->with('user:id,nom,prenom,telephone')
                ->latest()
                ->paginate(15)
                ->withQueryString(),
        ]);
    }

    public function operations(): Response
    {
        return Inertia::render('admin/operations/index', [
            'items' => Operation::query()
                ->with([
                    'user:id,nom,prenom,telephone',
                    'type_cotisation:id,code,libelle',
                    'objectif_epargne:id,libelle',
                    'paiement_entrant:id,reference_externe,montant_brut,statut',
                ])
                ->latest()
                ->paginate(20)
                ->withQueryString(),
        ]);
    }

    public function audit(): Response
    {
        return Inertia::render('admin/audit/index', [
            'items' => LogAudit::query()
                ->with('administrateur:id,nom,prenom,email')
                ->latest()
                ->paginate(20)
                ->withQueryString(),
        ]);
    }

    public function settings(): Response
    {
        return Inertia::render('admin/settings/index', [
            'items' => ParametreGlobal::query()
                ->with('administrateur:id,nom,prenom,email')
                ->latest('updated_at')
                ->paginate(20)
                ->withQueryString(),
        ]);
    }

    public function roles(): Response
    {
        return Inertia::render('admin/roles/index', [
            'items' => Role::query()
                ->with('permissions:id,name')
                ->withCount('permissions')
                ->orderBy('name')
                ->get(['id', 'name', 'guard_name']),
        ]);
    }

    public function permissions(): Response
    {
        return Inertia::render('admin/permissions/index', [
            'items' => Permission::query()
                ->orderBy('name')
                ->paginate(50)
                ->withQueryString(),
        ]);
    }
}
