<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['statut_precedent', 'statut_nouveau', 'message', 'motif', 'date_debut', 'date_fin', 'modifie_par', 'ip_adresse'])]
class PlateformeHistorique extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected function casts(): array
    {
        return [
            'date_debut' => 'datetime',
            'date_fin'   => 'datetime',
        ];
    }

    public function modifiePar()
    {
        return $this->belongsTo(Administrateur::class, 'modifie_par');
    }
}
