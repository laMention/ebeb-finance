<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['user_id', 'libelle', 'montant_cible', 'montant_epargne', 'date_limite', 'est_actif', 'type_calcul', 'valeur'])]
class ObjectifEpargne extends Model
{
    use SoftDeletes, HasUuids;
    public $incrementing = false;
    protected $keyType = 'string';

    public function casts(): array {
        return [
            'est_actif'       => 'boolean',
            'date_limite'     => 'date',
            'montant_cible'   => 'float',
            'montant_epargne' => 'float',
            'valeur'          => 'float',
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
