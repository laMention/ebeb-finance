<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['user_id', 'categorie_professionnelle', 'metier', 'revenu_mensuel', 'date_debut_activite','date_fin_activite'])]
class InformationProfessionnelle extends Model
{
    //
    use SoftDeletes,HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    public function casts(): array
    {
        return [
            'date_debut_activite' => 'date',
            'date_fin_activite' => 'date',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
