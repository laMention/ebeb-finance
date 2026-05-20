<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['cle', 'valeur','description','modifie_par'])]
class ParametreGlobal extends Model
{
    //
    public function administrateur()
    {
        return $this->belongsTo(Administrateur::class, 'modifie_par');
    }
}
