<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['page_id', 'numero_version', 'titre', 'contenu', 'est_active', 'publie_le'])]
class CguVersion extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $casts = [
        'est_active' => 'boolean',
        'publie_le'  => 'datetime',
    ];

    public function page()
    {
        return $this->belongsTo(Page::class, 'page_id');
    }

    public function acceptations()
    {
        return $this->hasMany(CguAcceptation::class, 'cgu_version_id');
    }
}
