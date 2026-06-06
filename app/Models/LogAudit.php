<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['administrateur_id', 'action', 'entite_cible', 'entite_id', 'donnees_avant', 'donnees_apres', 'ip_adresse'])]
class LogAudit extends Model
{
    //
    use SoftDeletes,HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    public function administrateur()
    {
        return $this->belongsTo(Administrateur::class);
    }
}
