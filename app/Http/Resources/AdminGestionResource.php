<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminGestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $roles = $this->roles;
        $role  = $roles->first();

        return [
            'id'            => $this->id,
            'nom'           => $this->nom,
            'prenom'        => $this->prenom,
            'email'         => $this->email,
            'telephone'     => $this->telephone,
            'ville'         => $this->ville,
            'adresse'       => $this->adresse,
            'photo_profil'  => $this->photo_profil,
            'statut_compte' => $this->statut_compte,
            'est_archive'   => (bool) $this->deleted_at,
            'is_super_admin'=> $this->isSuperAdmin(),
            'role'          => $role ? [
                'id'           => $role->id,
                'name'         => $role->name,
                'display_name' => $role->display_name ?? null,
            ] : null,
            'permissions_directes_count' => $this->relationLoaded('permissions')
                ? $this->permissions->count()
                : null,
            'created_at'    => $this->created_at?->format('Y-m-d H:i'),
            'updated_at'    => $this->updated_at?->format('Y-m-d H:i'),
            'deleted_at'    => $this->deleted_at?->format('Y-m-d H:i'),
        ];
    }
}
