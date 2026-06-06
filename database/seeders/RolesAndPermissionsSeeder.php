<?php

namespace Database\Seeders;

use App\Models\Administrateur;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'admin.access',
            'kyc.review',
            'operations.view',
            'audit.view',
            'settings.manage',
            'roles.manage',
            'permissions.manage',
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'admin',
            ]);
        }

        $superAdmin = Role::firstOrCreate([
            'name' => 'super-admin',
            'guard_name' => 'admin',
        ]);
        $superAdmin->syncPermissions(Permission::where('guard_name', 'admin')->get());

        $operationsAdmin = Role::firstOrCreate([
            'name' => 'operations-admin',
            'guard_name' => 'admin',
        ]);
        $operationsAdmin->syncPermissions([
            'admin.access',
            'operations.view',
            'kyc.review',
        ]);

        $auditAdmin = Role::firstOrCreate([
            'name' => 'audit-admin',
            'guard_name' => 'admin',
        ]);
        $auditAdmin->syncPermissions([
            'admin.access',
            'audit.view',
        ]);

        $configAdmin = Role::firstOrCreate([
            'name' => 'config-admin',
            'guard_name' => 'admin',
        ]);
        $configAdmin->syncPermissions([
            'admin.access',
            'settings.manage',
            'roles.manage',
            'permissions.manage',
        ]);

        $firstAdmin = Administrateur::query()->first();
        if ($firstAdmin && ! $firstAdmin->hasRole('super-admin')) {
            $firstAdmin->assignRole('super-admin');
        }
    }
}
