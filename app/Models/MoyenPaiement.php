<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['libelle','code','logo','operateur','par_defaut','est_actif'])]
class MoyenPaiement extends Model
{
    use HasUuids, SoftDeletes;

    public $incrementing = false;
    protected $keyType = 'string';

    public function comptes_mobile_money()
    {
        return $this->hasMany(CompteMobileMoney::class, 'moyen_paiement_id');
    }
}

