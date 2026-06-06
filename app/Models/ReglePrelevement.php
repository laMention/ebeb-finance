<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['user_id', 'type_cotisation_id', 'type_calcul', 'valeur', 'est_actif', 'ordre_priorite'])]
class ReglePrelevement extends Model
{
    //
    use SoftDeletes, HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function type_cotisation()
    {
        return $this->belongsTo(TypeCotisation::class, 'type_cotisation_id');
    }
}
