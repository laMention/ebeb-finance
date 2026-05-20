<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['user_id', 'total_epargne', 'total_verse_cotisations', 'total_commissions_payees', 'total_recu_brut', 'mois_reference','annee_reference','recalcule_at'])]
class PortefeuilleEpargne extends Model
{
    //
    use SoftDeletes;

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
