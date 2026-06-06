<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['user_id', 'montant_revenu', 'montant_cotisation_regime_base', 'montant_cotisation_regime_complementaire', 'montant_cotisation_mensuelle', 'montant_cotisation_trimestrielle'])]
class DeclarationRevenu extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';
    //
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
