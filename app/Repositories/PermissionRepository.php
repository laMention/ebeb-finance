<?php

namespace App\Repositories;

use App\Models\Permission;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\Permission\PermissionRegistrar;

class PermissionRepository
{
    public function paginate(array $params): LengthAwarePaginator
    {
        $query = Permission::where('guard_name', 'admin');

        if (!empty($params['search'])) {
            $q = $params['search'];
            $query->where(function ($qb) use ($q) {
                $qb->where('name', 'like', "%{$q}%")
                   ->orWhere('display_name', 'like', "%{$q}%")
                   ->orWhere('module', 'like', "%{$q}%");
            });
        }

        if (!empty($params['module'])) {
            $query->where('module', $params['module']);
        }

        $perPage = min((int) ($params['per_page'] ?? 50), 200);
        $page    = (int) ($params['page'] ?? 1);

        return $query->orderBy('module')->orderBy('name')->paginate($perPage, ['*'], 'page', $page);
    }

    public function all(): \Illuminate\Database\Eloquent\Collection
    {
        return Permission::where('guard_name', 'admin')
            ->orderBy('module')
            ->orderBy('name')
            ->get();
    }

    public function modules(): array
    {
        return Permission::where('guard_name', 'admin')
            ->whereNotNull('module')
            ->distinct()
            ->orderBy('module')
            ->pluck('module')
            ->toArray();
    }

    public function findById(int $id): Permission
    {
        return Permission::findOrFail($id);
    }

    public function create(array $data): Permission
    {
        $permission = Permission::create([
            'name'         => $data['name'],
            'guard_name'   => 'admin',
            'display_name' => $data['display_name'] ?? null,
            'module'       => $data['module'] ?? null,
            'description'  => $data['description'] ?? null,
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $permission;
    }

    public function update(Permission $permission, array $data): Permission
    {
        $champs = [];
        if (isset($data['name']))         $champs['name']         = $data['name'];
        if (isset($data['display_name'])) $champs['display_name'] = $data['display_name'];
        if (isset($data['module']))       $champs['module']       = $data['module'];
        if (isset($data['description']))  $champs['description']  = $data['description'];

        if (!empty($champs)) $permission->update($champs);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $permission->fresh();
    }
}
