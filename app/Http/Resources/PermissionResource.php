<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PermissionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'display_name' => $this->display_name,
            'module'       => $this->module,
            'description'  => $this->description,
            'guard_name'   => $this->guard_name,
            'created_at'   => $this->created_at?->format('Y-m-d H:i'),
        ];
    }
}
