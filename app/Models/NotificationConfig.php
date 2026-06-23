<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class NotificationConfig extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType   = 'string';

    protected $fillable = ['canal', 'est_actif', 'fournisseur', 'configuration'];

    protected $casts = [
        'est_actif' => 'boolean',
    ];

    // Chiffrement transparent des credentials
    public function getConfigurationAttribute(?string $value): array
    {
        if (!$value) return [];
        try {
            return json_decode(Crypt::decryptString($value), true) ?? [];
        } catch (\Exception) {
            return [];
        }
    }

    public function setConfigurationAttribute(array $value): void
    {
        $this->attributes['configuration'] = Crypt::encryptString(json_encode($value));
    }
}
