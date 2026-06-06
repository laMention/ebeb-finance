<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['cle', 'valeur','description','modifie_par'])]
class ParametreGlobal extends Model
{
    //
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';
    public function administrateur()
    {
        return $this->belongsTo(Administrateur::class, 'modifie_par');
    }
}
