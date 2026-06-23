<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['seuil_pourcentage', 'seuil_montant', 'est_actif', 'description', 'modifie_par'])]
class SeuilPrelevement extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType   = 'string';

    protected function casts(): array
    {
        return [
            'seuil_pourcentage' => 'float',
            'seuil_montant'     => 'float',
            'est_actif'         => 'boolean',
        ];
    }

    /**
     * Retourne la configuration singleton (crée un enregistrement vide si absent).
     */
    public static function singleton(): self
    {
        return static::firstOrCreate([], [
            'seuil_pourcentage' => null,
            'seuil_montant'     => null,
            'est_actif'         => true,
        ]);
    }
}
