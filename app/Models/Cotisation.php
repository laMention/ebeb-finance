<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['user_id', 'type_cotisation_id', 'mois', 'annee', 'montant_verse', 'montant_objectif', 'statut', 'numero_adherent', 'date_paiement'])]
class Cotisation extends Model
{
    //
    use SoftDeletes;

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function typeCotisation()
    {
        return $this->belongsTo(TypeCotisation::class);
    }
}
