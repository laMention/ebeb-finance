<?php

namespace App\Services;

use App\Http\Resources\PermissionResource;
use App\Models\Permission;
use App\Repositories\PermissionRepository;

class PermissionService
{
    public function __construct(private readonly PermissionRepository $permissionRepo) {}

    public function lister(array $params): array
    {
        try {
            $paginated = $this->permissionRepo->paginate($params);
            return [
                'success' => true,
                'message' => 'Liste des permissions',
                'data'    => PermissionResource::collection($paginated->getCollection()),
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

    public function listerParModule(): array
    {
        try {
            $toutes = $this->permissionRepo->all();
            $grouped = $toutes->groupBy('module')->map(fn($perms, $module) => [
                'module'      => $module ?? 'Général',
                'permissions' => PermissionResource::collection($perms)->resolve(),
            ])->values();
            return ['success' => true, 'message' => 'Permissions groupées par module', 'data' => $grouped];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function modules(): array
    {
        try {
            return ['success' => true, 'message' => 'Modules', 'data' => $this->permissionRepo->modules()];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function creer(array $data): array
    {
        try {
            $existe = Permission::where('name', $data['name'])->where('guard_name', 'admin')->exists();
            if ($existe) {
                return ['success' => false, 'message' => "Une permission avec le code \"{$data['name']}\" existe déjà."];
            }
            $permission = $this->permissionRepo->create($data);
            return ['success' => true, 'message' => 'Permission créée avec succès', 'data' => new PermissionResource($permission)];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function modifier(Permission $permission, array $data): array
    {
        try {
            if (isset($data['name']) && $data['name'] !== $permission->name) {
                $existe = Permission::where('name', $data['name'])->where('guard_name', 'admin')->exists();
                if ($existe) {
                    return ['success' => false, 'message' => "Une permission avec le code \"{$data['name']}\" existe déjà."];
                }
            }
            $permission = $this->permissionRepo->update($permission, $data);
            return ['success' => true, 'message' => 'Permission modifiée avec succès', 'data' => new PermissionResource($permission)];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
