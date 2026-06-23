<?php

namespace App\Repositories;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\Permission\PermissionRegistrar;

class RoleRepository
{
    public function paginate(array $params): LengthAwarePaginator
    {
        $query = Role::withCount(['permissions'])
            ->with('permissions')
            ->where('guard_name', 'admin');

        if (!empty($params['search'])) {
            $q = $params['search'];
            $query->where(function ($qb) use ($q) {
                $qb->where('name', 'like', "%{$q}%")
                   ->orWhere('display_name', 'like', "%{$q}%");
            });
        }

        if (isset($params['is_archived'])) {
            $query->where('is_archived', (bool) $params['is_archived']);
        }

        $perPage = min((int) ($params['per_page'] ?? 20), 100);
        $page    = (int) ($params['page'] ?? 1);

        return $query->orderBy('name')->paginate($perPage, ['*'], 'page', $page);
    }

    public function findById(int $id): Role
    {
        return Role::withCount(['permissions'])->with('permissions')->findOrFail($id);
    }

    public function create(array $data): Role
    {
        $role = Role::create([
            'name'         => $data['name'],
            'guard_name'   => 'admin',
            'display_name' => $data['display_name'] ?? null,
            'description'  => $data['description'] ?? null,
        ]);

        if (!empty($data['permission_ids'])) {
            $permissions = Permission::whereIn('id', $data['permission_ids'])->get();
            $role->syncPermissions($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $role->loadCount(['permissions'])->load('permissions');
    }

    public function update(Role $role, array $data): Role
    {
        $champs = [];
        if (isset($data['name']))         $champs['name']         = $data['name'];
        if (isset($data['display_name'])) $champs['display_name'] = $data['display_name'];
        if (isset($data['description']))  $champs['description']  = $data['description'];

        if (!empty($champs)) $role->update($champs);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $role->fresh()->loadCount(['permissions'])->load('permissions');
    }

    public function archive(Role $role): Role
    {
        $role->update(['is_archived' => true]);
        return $role->fresh();
    }

    public function restore(Role $role): Role
    {
        $role->update(['is_archived' => false]);
        return $role->fresh();
    }

    public function syncPermissions(Role $role, array $permissionIds): Role
    {
        $permissions = Permission::whereIn('id', $permissionIds)->get();
        $role->syncPermissions($permissions);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $role->fresh()->loadCount(['permissions'])->load('permissions');
    }

    public function all(): \Illuminate\Database\Eloquent\Collection
    {
        return Role::where('guard_name', 'admin')
            ->where('is_archived', false)
            ->withCount('permissions')
            ->orderBy('name')
            ->get();
    }
}
