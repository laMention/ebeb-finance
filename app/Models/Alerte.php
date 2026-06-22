<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Alerte extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'type_alerte',
        'titre',
        'description',
        'niveau',
        'est_lu',
        'date_lecture',
        'lien_vers_action_concerne',
    ];

    protected function casts(): array
    {
        return [
            'est_lu'       => 'boolean',
            'date_lecture' => 'datetime',
        ];
    }
}
