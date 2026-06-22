<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    protected $fillable = ['name', 'guard_name', 'display_name', 'description', 'is_archived'];

    protected function casts(): array
    {
        return ['is_archived' => 'boolean'];
    }

    public function scopeActif($query)
    {
        return $query->where('is_archived', false);
    }

    public function scopeArchive($query)
    {
        return $query->where('is_archived', true);
    }
}
