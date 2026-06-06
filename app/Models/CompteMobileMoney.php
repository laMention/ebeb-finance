<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['user_id', 'operateur', 'numero_compte', 'est_principal', 'est_actif'])]
class CompteMobileMoney extends Model
{
    use SoftDeletes,HasUuids;
    //
    public $incrementing = false;
    protected $keyType = 'string';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function paiements_entrants()
    {
        return $this->hasMany(PaiementEntrant::class, 'compte_mobile_money_id');
    }
}
