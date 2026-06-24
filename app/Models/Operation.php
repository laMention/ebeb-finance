<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['user_id', 'type_cotisation_id', 'objectif_epargne_id', 'paiement_entrant_id', 'operation_parent_id', 'montant', 'type_operation', 'description', 'libelle', 'statut', 'reference', 'date_operation'])]
class Operation extends Model
{
    use SoftDeletes, HasUuids;

    public $incrementing = false;
    protected $keyType   = 'string';

    const TYPES_CREDIT = ['PAIEMENT_CLIENT', 'REVERSEMENT', 'REVERSEMENT_ESCROW'];

    const TYPES_DEBIT = [
        'EPARGNE', 'COTISATION_CNPS', 'COTISATION_AMU', 'COTISATION_PERSONNALISEE',
        'ASSURANCE_PERSONNALISEE', 'COMMISSION_PLATEFORME', 'COMMISSION',
        'VIREMENT', 'PRELEVEMENT_COTISATION', 'PRELEVEMENT_EPARGNE',
        'RETRAIT_EPARGNE', 'RETRAIT_COTISATION', 'AJUSTEMENT', 'REPORT_COTISATION', 'ESCROW',
    ];

    protected function casts(): array
    {
        return [
            'montant'        => 'decimal:2',
            'date_operation' => 'datetime',
        ];
    }

    public function user():BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function type_cotisation():BelongsTo
    {
        return $this->belongsTo(TypeCotisation::class, 'type_cotisation_id');
    }

    public function objectif_epargne():BelongsTo
    {
        return $this->belongsTo(ObjectifEpargne::class, 'objectif_epargne_id');
    }

    public function escrow():HasOne
    {
        return $this->hasOne(Escrow::class, 'operation_id');
    }

    public function paiement_entrant():BelongsTo
    {
        return $this->belongsTo(PaiementEntrant::class, 'paiement_entrant_id');
    }

    public function operation_parent():BelongsTo
    {
        return $this->belongsTo(Operation::class, 'operation_parent_id');
    }

    public function sous_operations(): HasMany
    {
        return $this->hasMany(Operation::class, 'operation_parent_id');
    }

    public function reversements(): BelongsToMany
    {
        return $this->belongsToMany(Reversement::class, 'reversement_operations')
                    ->withPivot(['montant', 'statut'])
                    ->withTimestamps();
    }

    public function reversementOperations(): HasMany
    {
        return $this->hasMany(ReversementOperation::class);
    }
}
