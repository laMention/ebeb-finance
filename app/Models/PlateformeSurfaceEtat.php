<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['surface', 'statut', 'message', 'modifie_par'])]
class PlateformeSurfaceEtat extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    public function modifiePar()
    {
        return $this->belongsTo(Administrateur::class, 'modifie_par');
    }
}
