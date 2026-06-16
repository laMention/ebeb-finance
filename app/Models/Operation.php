<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
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
            'montant'        => 'float',
            'date_operation' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function type_cotisation()
    {
        return $this->belongsTo(TypeCotisation::class, 'type_cotisation_id');
    }

    public function objectif_epargne()
    {
        return $this->belongsTo(ObjectifEpargne::class, 'objectif_epargne_id');
    }

    public function escrow()
    {
        return $this->hasOne(Escrow::class, 'operation_id');
    }

    public function paiement_entrant()
    {
        return $this->belongsTo(PaiementEntrant::class, 'paiement_entrant_id');
    }

    public function operation_parent()
    {
        return $this->belongsTo(Operation::class, 'operation_parent_id');
    }

    public function sous_operations()
    {
        return $this->hasMany(Operation::class, 'operation_parent_id');
    }
}
