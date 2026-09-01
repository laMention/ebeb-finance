<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'question', 'reponse', 'categorie', 'statut', 'ordre', 'cree_par', 'modifie_par',
])]
class Faq extends Model
{
    use HasUuids, SoftDeletes;

    public $incrementing = false;
    protected $keyType   = 'string';

    protected $casts = [
        'ordre' => 'integer',
    ];

    public function createur(): BelongsTo
    {
        return $this->belongsTo(Administrateur::class, 'cree_par');
    }

    public function modificateur(): BelongsTo
    {
        return $this->belongsTo(Administrateur::class, 'modifie_par');
    }
}
