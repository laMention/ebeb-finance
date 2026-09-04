<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Deuxième facteur de connexion admin (2FA) — voir AdminTwoFactorService.
 * L'id de ce modèle sert directement de « challenge_id » côté API/frontend.
 */
#[Fillable(['administrateur_id', 'code_otp', 'canal', 'est_utilise', 'tentatives', 'expire_at'])]
class AdminSessionOtp extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected function casts(): array
    {
        return [
            'est_utilise' => 'boolean',
            'expire_at'   => 'datetime',
        ];
    }

    public function administrateur()
    {
        return $this->belongsTo(Administrateur::class);
    }
}
