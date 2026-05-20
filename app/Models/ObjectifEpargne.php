<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['user_id', 'libelle', 'montant_cible', 'montant_epargne', 'date_limite', 'est_actif'])]
class ObjectifEpargne extends Model
{
    //
    use SoftDeletes;

    public function casts(): array {
        return [
            'est_actif' => 'boolean',
            'date_limite' => 'date',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function operations()
    {
        return $this->hasMany(Operation::class, 'objectif_epargne_id');
    }
}
