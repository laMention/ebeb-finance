<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['user_id', 'compte_mobile_money_id', 'montant_brut', 'statut', 'reference_externe','operateur_source','qr_code_ref'])]
class PaiementEntrant extends Model
{
    //
    use SoftDeletes;

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function compte_mobile_money()
    {
        return $this->belongsTo(CompteMobileMoney::class, 'compte_mobile_money_id');
    }

    public function operation()
    {
        return $this->hasOne(Operation::class, 'paiement_entrant_id');
    }
}
