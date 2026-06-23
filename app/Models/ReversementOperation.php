<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['reversement_id', 'operation_id', 'montant'])]

class ReversementOperation extends Model
{
    use SoftDeletes, HasUlids;
    //
    protected $table = 'reversement_operations';


    public $incrementing = false;
    protected $keyType   = 'string';


    protected function casts(): array
    {
        return [
            'montant'            => 'float',
        ];
    }

    public function reversement(): BelongsTo
    {
        return $this->belongsTo(Reversement::class);
    }

    public function operation(): BelongsTo
    {
        return $this->belongsTo(Operation::class);
    }
}