<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'operation_id', 'operation_remboursement_id', 'user_id', 'type_cotisation_id',
    'administrateur_id', 'montant_preleve', 'montant_rembourse', 'motif',
])]
class Remboursement extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $casts = [
        'montant_preleve'   => 'decimal:2',
        'montant_rembourse' => 'decimal:2',
    ];

    public function operation()
    {
        return $this->belongsTo(Operation::class, 'operation_id');
    }

    public function operationRemboursement()
    {
        return $this->belongsTo(Operation::class, 'operation_remboursement_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function typeCotisation()
    {
        return $this->belongsTo(TypeCotisation::class, 'type_cotisation_id');
    }

    public function administrateur()
    {
        return $this->belongsTo(Administrateur::class);
    }
}
