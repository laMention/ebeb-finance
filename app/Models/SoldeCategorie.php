<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['user_id', 'type_cotisation_id', 'type_categorie', 'libelle', 'solde', 'total_verse', 'total_reporte'])]
class SoldeCategorie extends Model
{
    use HasUuids, SoftDeletes;

    public $incrementing = false;
    protected $keyType   = 'string';

    protected function casts(): array
    {
        return [
            'solde'          => 'float',
            'total_verse'    => 'float',
            'total_reporte'  => 'float',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function type_cotisation()
    {
        return $this->belongsTo(TypeCotisation::class);
    }
}
