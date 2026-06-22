<?php

namespace App\Services;

use App\Http\Resources\AdminRbacResource;
use App\Models\Administrateur;
use App\Models\Permission;
use App\Models\Role;

class AdminRoleService
{
    public function listerAdmins(array $params): array
    {
        try {
            $query = Administrateur::with(['roles.permissions', 'permissions']);

            if (!empty($params['search'])) {
                $q = $params['search'];
                $query->where(function ($qb) use ($q) {
                    $qb->where('nom', 'like', "%{$q}%")
                       ->orWhere('prenom', 'like', "%{$q}%")
                       ->orWhere('email', 'like', "%{$q}%");
                });
            }

            if (!empty($params['role'])) {
                $query->whereHas('roles', fn($r) => $r->where('name', $params['role']));
            }

            $perPage = min((int) ($params['per_page'] ?? 20), 100);
            $page    = (int) ($params['page'] ?? 1);

            $paginated = $query->orderBy('nom')->orderBy('prenom')->paginate($perPage, ['*'], 'page', $page);

            return [
                'success' => true,
                'message' => 'Liste des administrateurs avec RBAC',
                'data'    => AdminRbacResource::collection($paginated->getCollection()),
                'meta'    => [
                    'current_page' => $paginated->currentPage(),
                    'last_page'    => $paginated->lastPage(),
                    'per_page'     => $paginated->perPage(),
                    'total'        => $paginated->total(),
                ],
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function afficherAdmin(Administrateur $admin): array
    {
        try {
            $admin->load(['roles.permissions', 'permissions']);

            $toutesPermissions = Permission::where('guard_name', 'admin')
                ->orderBy('module')
                ->orderBy('name')
                ->get();

            $permissionsEffectives = $admin->getAllPermissions();

            return [
                'success' => true,
                'message' => 'Détail RBAC de l\'administrateur',
                'data'    => [
                    'admin'                  => new AdminRbacResource($admin),
                    'toutes_permissions'     => $toutesPermissions->map(fn($p) => [
                        'id'              => $p->id,
                        'name'            => $p->name,
                        'display_name'    => $p->display_name,
                        'module'          => $p->module,
                        'description'     => $p->description,
                        'via_role'        => $permissionsEffectives->contains($p->id) && !$admin->permissions->contains($p->id),
                        'directe'         => $admin->permissions->contains($p->id),
                        'effective'       => $permissionsEffectives->contains($p->id),
                    ]),
                    'permissions_effectives' => $permissionsEffectives->count(),
                ],
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function assignerRole(Administrateur $admin, array $data): array
    {
        try {
            if ($admin->isSuperAdmin()) {
                return ['success' => false, 'message' => 'Le rôle du super-admin ne peut pas être modifié.'];
            }

            $role = Role::where('guard_name', 'admin')
                ->where('is_archived', false)
                ->findOrFail($data['role_id']);

            $admin->syncRoles([$role]);
            $admin->load(['roles.permissions', 'permissions']);

            $libelleRole = $role->display_name ?? $role->name;

            return ['success' => true, 'message' => "Rôle \"{$libelleRole}\" assigné à {$admin->prenom} {$admin->nom}", 'data' => new AdminRbacResource($admin)];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function retirerRole(Administrateur $admin): array
    {
        try {
            if ($admin->isSuperAdmin()) {
                return ['success' => false, 'message' => 'Le rôle du super-admin ne peut pas être modifié.'];
            }

            $admin->syncRoles([]);
            $admin->load(['roles.permissions', 'permissions']);

            return ['success' => true, 'message' => 'Rôle retiré', 'data' => new AdminRbacResource($admin)];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function assignerPermissionsDirectes(Administrateur $admin, array $data): array
    {
        try {
            if ($admin->isSuperAdmin()) {
                return ['success' => false, 'message' => 'Les permissions du super-admin sont gérées automatiquement.'];
            }

            $permissions = Permission::where('guard_name', 'admin')
                ->whereIn('id', $data['permission_ids'])
                ->get();

            $admin->syncPermissions($permissions);
            $admin->load(['roles.permissions', 'permissions']);

            return [
                'success' => true,
                'message' => count($data['permission_ids']) . ' permission(s) directe(s) assignée(s)',
                'data'    => new AdminRbacResource($admin),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function retirerPermissionsDirectes(Administrateur $admin): array
    {
        try {
            if ($admin->isSuperAdmin()) {
                return ['success' => false, 'message' => 'Les permissions du super-admin sont gérées automatiquement.'];
            }

            $admin->syncPermissions([]);
            $admin->load(['roles.permissions', 'permissions']);

            return ['success' => true, 'message' => 'Permissions directes retirées', 'data' => new AdminRbacResource($admin)];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
