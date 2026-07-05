<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'libelle', 'code', 'categorie', 'est_obligatoire', 'est_actif',
    'description', 'user_id',
    'default_type_calcul', 'default_valeur', 'default_est_actif', 'default_date_entree_en_vigueur',
])]
class TypeCotisation extends Model
{
    //
    use SoftDeletes, HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $casts = [
        'est_obligatoire'                => 'boolean',
        'est_actif'                      => 'boolean',
        'default_valeur'                 => 'decimal:2',
        'default_est_actif'              => 'boolean',
        'default_date_entree_en_vigueur' => 'date',
    ];

    public function operations()
    {
        return $this->hasMany(Operation::class, 'type_cotisation_id');
    }

    public function regle_prelevements()
    {
        return $this->hasMany(ReglePrelevement::class, 'type_cotisation_id');
    }

    public function cotisations()
    {
        return $this->hasMany(Cotisation::class, 'type_cotisation_id');
    }

    public function user(){
        return $this->belongsTo(User::class);
    }
}
