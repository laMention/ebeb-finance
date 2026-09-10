<?php
use App\Models\Permission;
use App\Models\Role;

$perm = Permission::where('name', 'transactions.rembourser')->first();
echo "Permission créée: " . ($perm ? 'OUI' : 'NON') . "\n";

foreach (['super-admin', 'admin', 'gestionnaire-financier', 'support', 'auditeur'] as $roleName) {
    $role = Role::where('name', $roleName)->where('guard_name', 'admin')->first();
    $has = $role?->hasPermissionTo('transactions.rembourser') ?? false;
    echo "$roleName: " . ($has ? 'a la permission' : 'PAS la permission') . "\n";
}
