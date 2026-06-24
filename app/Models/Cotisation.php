<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['user_id', 'type_cotisation_id', 'mois', 'annee', 'montant_verse', 'montant_objectif', 'montant_restant', 'statut', 'numero_adherent', 'date_paiement', 'date_debut', 'date_fin'])]
class Cotisation extends Model
{
    use SoftDeletes, HasUuids;
    public $incrementing = false;
    protected $keyType   = 'string';

    protected function casts(): array
    {
        return [
            'montant_verse'    => 'decimal:2',
            'montant_objectif' => 'decimal:2',
            'montant_restant'  => 'decimal:2',
            'date_debut'       => 'date',
            'date_fin'         => 'date',
            'date_paiement'    => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function typeCotisation()
    {
        return $this->belongsTo(TypeCotisation::class);
    }
}
