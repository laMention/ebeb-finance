<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['user_id', 'type_cotisation_id', 'objectif_epargne_id', 'montant', 'type_operation', 'description', 'statut'])]
class Operation extends Model
{
    //
    use SoftDeletes;

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function type_cotisation()
    {
        return $this->belongsTo(TypeCotisation::class, 'type_cotisation_id');
    }

    public function objectif_epargne()
    {
        return $this->belongsTo(ObjectifEpargne::class, 'objectif_epargne_id');
    }

    public function escrow()
    {
        return $this->hasOne(Escrow::class, 'operation_id');
    }

    public function paiement_entrant()
    {
        return $this->belongsTo(PaiementEntrant::class, 'paiement_entrant_id');
    }
}
