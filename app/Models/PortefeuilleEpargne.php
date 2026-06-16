<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['user_id', 'total_epargne', 'solde_epargne_disponible', 'total_verse_cotisations', 'total_commissions_payees', 'total_recu_brut', 'montant_net_total', 'mois_reference', 'annee_reference', 'recalcule_at'])]
class PortefeuilleEpargne extends Model
{
    use SoftDeletes, HasUuids;

    public $incrementing = false;
    protected $keyType   = 'string';

    protected function casts(): array
    {
        return [
            'total_epargne'            => 'float',
            'solde_epargne_disponible' => 'float',
            'total_verse_cotisations'  => 'float',
            'total_commissions_payees' => 'float',
            'total_recu_brut'          => 'float',
            'montant_net_total'        => 'float',
            'recalcule_at'             => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
