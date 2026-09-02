<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'nom', 'code', 'type', 'est_actif',
    'url_api_reversement', 'url_api_consultation', 'url_webhook',
    'methode_authentification', 'identifiants_api', 'format_echange',
])]

class PartenairesFinancier extends Model
{
    use SoftDeletes, HasUlids;

    public $incrementing = false;
    protected $keyType   = 'string';

    protected function casts(): array
    {
        return [
            'est_actif'        => 'boolean',
            'identifiants_api' => 'encrypted:array',
        ];
    }

    public function reversements()
    {
        return $this->hasMany(Reversement::class, 'partenaires_financier_id');
    }

    public function comptesDestination(): HasMany
    {
        return $this->hasMany(PartenaireCompteDestination::class, 'partenaires_financier_id');
    }
}
