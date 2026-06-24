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
            'total_epargne'            => 'decimal:2',
            'solde_epargne_disponible' => 'decimal:2',
            'total_verse_cotisations'  => 'decimal:2',
            'total_commissions_payees' => 'decimal:2',
            'total_recu_brut'          => 'decimal:2',
            'montant_net_total'        => 'decimal:2',
            'recalcule_at'             => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
