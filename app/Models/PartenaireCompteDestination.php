<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['partenaires_financier_id', 'libelle', 'type_compte', 'numero_compte', 'banque_operateur', 'est_actif', 'est_principal'])]
class PartenaireCompteDestination extends Model
{
    use HasUuids;

    protected $table     = 'partenaire_comptes_destination';
    public $incrementing = false;
    protected $keyType   = 'string';

    protected function casts(): array
    {
        return [
            'est_actif'     => 'boolean',
            'est_principal' => 'boolean',
        ];
    }

    public function partenaire(): BelongsTo
    {
        return $this->belongsTo(PartenairesFinancier::class, 'partenaires_financier_id');
    }
}
