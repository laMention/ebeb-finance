<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'reference', 'montant_total', 'date_reversement', 'statut', 'initie_par',
    'partenaires_financier_id',
    'periode_debut', 'periode_fin', 'date_execution',
    'motif_annulation', 'annule_par',
])]
class Reversement extends Model
{
    use SoftDeletes, HasUuids;

    public $incrementing = false;
    protected $keyType   = 'string';

    // Statuts valides
    const STATUT_EN_ATTENTE = 'EN_ATTENTE';
    const STATUT_EN_COURS   = 'EN_COURS';
    const STATUT_REVERSE    = 'REVERSE';
    const STATUT_ANNULE     = 'ANNULE';
    const STATUT_ECHEC      = 'ECHEC';

    protected function casts(): array
    {
        return [
            'montant_total'    => 'decimal:2',
            'date_reversement' => 'datetime',
            'date_execution'   => 'datetime',
            'periode_debut'    => 'date',
            'periode_fin'      => 'date',
        ];
    }

    public function partenaire(): BelongsTo
    {
        return $this->belongsTo(PartenairesFinancier::class, 'partenaires_financier_id');
    }

    // kept for backward compat
    public function paternaire_financier(): BelongsTo
    {
        return $this->belongsTo(PartenairesFinancier::class, 'partenaires_financier_id');
    }

    public function operations(): BelongsToMany
    {
        return $this->belongsToMany(Operation::class, 'reversement_operations')
                    ->withPivot(['montant'])
                    ->withTimestamps();
    }

    public function reversementOperations(): HasMany
    {
        return $this->hasMany(ReversementOperation::class);
    }
}
