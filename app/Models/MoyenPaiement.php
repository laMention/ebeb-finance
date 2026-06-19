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

    public function configurations_api()
    {
        return $this->hasMany(ConfigurationApiOperateur::class, 'moyen_paiement_id');
    }

    /** Préfère PRODUCTION si plusieurs configs actives coexistent. */
    public function configurationApiActive()
    {
        return $this->hasOne(ConfigurationApiOperateur::class, 'moyen_paiement_id')
                    ->where('est_actif', true)
                    ->orderByRaw("CASE WHEN environnement = 'PRODUCTION' THEN 0 ELSE 1 END");
    }
}

