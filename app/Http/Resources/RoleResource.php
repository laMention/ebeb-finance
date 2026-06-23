<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'name'             => $this->name,
            'display_name'     => $this->display_name,
            'description'      => $this->description,
            'is_archived'      => (bool) $this->is_archived,
            'guard_name'       => $this->guard_name,
            'permissions_count'=> $this->whenCounted('permissions'),
            'permissions'      => PermissionResource::collection($this->whenLoaded('permissions')),
            'created_at'       => $this->created_at?->format('Y-m-d H:i'),
            'updated_at'       => $this->updated_at?->format('Y-m-d H:i'),
        ];
    }
}
