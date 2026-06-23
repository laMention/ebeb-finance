<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminRbacResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $roles = $this->roles;
        $role  = $roles->first();

        $permissionsDirectes  = $this->relationLoaded('permissions') ? $this->permissions : collect();
        $permissionsViaRole   = ($role && $role->relationLoaded('permissions')) ? $role->permissions : collect();

        return [
            'id'                      => $this->id,
            'nom'                     => $this->nom,
            'prenom'                  => $this->prenom,
            'email'                   => $this->email,
            'statut_compte'           => $this->statut_compte,
            'is_super_admin'          => $this->isSuperAdmin(),
            'role'                    => $role ? [
                'id'           => $role->id,
                'name'         => $role->name,
                'display_name' => $role->display_name ?? null,
                'description'  => $role->description ?? null,
            ] : null,
            'permissions_via_role'    => PermissionResource::collection($permissionsViaRole),
            'permissions_directes'    => PermissionResource::collection($permissionsDirectes),
            'direct_permissions_count'=> $permissionsDirectes->count(),
            'roles_count'             => $roles->count(),
        ];
    }
}
