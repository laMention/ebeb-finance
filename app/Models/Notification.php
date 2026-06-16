<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['user_id', 'canal', 'type', 'titre', 'contenu', 'est_envoye', 'envoye_le', 'est_lu', 'lu_le'])]
class Notification extends Model
{
    use SoftDeletes, HasUuids;

    public $incrementing = false;
    protected $keyType   = 'string';

    protected $casts = [
        'est_envoye' => 'boolean',
        'est_lu'     => 'boolean',
        'envoye_le'  => 'datetime',
        'lu_le'      => 'datetime',
        'contenu'    => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
