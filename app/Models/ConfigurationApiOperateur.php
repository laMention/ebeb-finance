<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'moyen_paiement_id', 'url_api', 'url_webhook',
    'identifiant_api', 'cle_api',
    'environnement', 'est_actif', 'notes',
])]
class ConfigurationApiOperateur extends Model
{
    use HasUuids, SoftDeletes;

    public $incrementing = false;
    protected $keyType   = 'string';

    protected $casts = [
        'cle_api'   => 'encrypted',
        'est_actif' => 'boolean',
    ];

    public function moyen_paiement(): BelongsTo
    {
        return $this->belongsTo(MoyenPaiement::class, 'moyen_paiement_id');
    }
}
