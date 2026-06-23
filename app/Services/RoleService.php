<?php

namespace App\Services;

use App\Http\Resources\RoleResource;
use App\Models\Role;
use App\Repositories\PermissionRepository;
use App\Repositories\RoleRepository;

class RoleService
{
    public function __construct(
        private readonly RoleRepository       $roleRepo,
        private readonly PermissionRepository $permissionRepo,
    ) {}

    public function lister(array $params): array
    {
        try {
            $paginated = $this->roleRepo->paginate($params);
            return [
                'success' => true,
                'message' => 'Liste des rôles',
                'data'    => RoleResource::collection($paginated->getCollection()),
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

    public function afficher(int $id): array
    {
        try {
            $role = $this->roleRepo->findById($id);
            $toutes = $this->permissionRepo->all();
            return [
                'success' => true,
                'message' => 'Détail du rôle',
                'data'    => [
                    'role'                => new RoleResource($role),
                    'toutes_permissions'  => $toutes->map(fn($p) => [
                        'id'           => $p->id,
                        'name'         => $p->name,
                        'display_name' => $p->display_name,
                        'module'       => $p->module,
                        'description'  => $p->description,
                        'assignee'     => $role->permissions->contains($p->id),
                    ]),
                ],
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function creer(array $data): array
    {
        try {
            $role = $this->roleRepo->create($data);
            return ['success' => true, 'message' => 'Rôle créé avec succès', 'data' => new RoleResource($role)];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function modifier(Role $role, array $data): array
    {
        try {
            if ($role->name === 'super-admin' && isset($data['name'])) {
                return ['success' => false, 'message' => 'Le rôle super-admin ne peut pas être renommé.'];
            }
            $role = $this->roleRepo->update($role, $data);
            return ['success' => true, 'message' => 'Rôle modifié avec succès', 'data' => new RoleResource($role)];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function archiver(Role $role): array
    {
        try {
            if ($role->name === 'super-admin') {
                return ['success' => false, 'message' => 'Le rôle super-admin ne peut pas être archivé.'];
            }
            $role = $this->roleRepo->archive($role);
            return ['success' => true, 'message' => 'Rôle archivé', 'data' => new RoleResource($role)];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function restaurer(Role $role): array
    {
        try {
            $role = $this->roleRepo->restore($role);
            return ['success' => true, 'message' => 'Rôle restauré', 'data' => new RoleResource($role)];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function syncPermissions(Role $role, array $permissionIds): array
    {
        try {
            if ($role->name === 'super-admin') {
                return ['success' => false, 'message' => 'Les permissions du super-admin sont gérées automatiquement.'];
            }
            $role = $this->roleRepo->syncPermissions($role, $permissionIds);
            return ['success' => true, 'message' => 'Permissions synchronisées', 'data' => new RoleResource($role)];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function tousRoles(): array
    {
        try {
            return [
                'success' => true,
                'message' => 'Tous les rôles',
                'data'    => RoleResource::collection($this->roleRepo->all()),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
