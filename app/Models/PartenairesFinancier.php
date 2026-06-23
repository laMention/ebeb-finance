<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['nom', 'code', 'type'])]

class PartenairesFinancier extends Model
{
    use SoftDeletes, HasUlids;

    public $incrementing = false;
    protected $keyType   = 'string';


    public function reversements()
    {
        return $this->hasMany(Reversement::class, 'partenaires_financier_id');
    }
}
