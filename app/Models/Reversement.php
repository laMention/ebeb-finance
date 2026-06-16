<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['reference', 'montant_total', 'date_reversement', 'statut', 'initie_par', 'partenaires_financier_id'])]
class Reversement extends Model
{
    //
    use SoftDeletes, HasUuids;

    public $incrementing = false;
    protected $keyType   = 'string';

    protected function casts(): array
    {
        return [
            'montant_total'            => 'float',
            'date_reversement'             => 'datetime',
        ];
    }

    public function paternaire_financier(): BelongsTo
    {
        return $this->belongsTo(PartenairesFinancier::class);
    }

    // Reversement a plusieurs Opérations via la table pivot
    public function operations(): BelongsToMany
    {
        return $this->belongsToMany(Operation::class, 'reversement_operations')
                    ->withPivot(['montant', 'statut']) // tes colonnes pivot si existantes
                    ->withTimestamps();
    }

    public function reversementOperations(): HasMany
    {
        return $this->hasMany(ReversementOperation::class);
    }
}
