<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['user_id', 'operation_id', 'montant', 'statut', 'raison_blocage', 'libere_at'])]
class Escrow extends Model
{
    //
    use SoftDeletes;

    public function casts(): array
    {
        return [
            'montant' => 'float',
            'libere_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function operation()
    {
        return $this->belongsTo(Operation::class);
    }
}
