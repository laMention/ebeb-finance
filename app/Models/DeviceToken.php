<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['user_id', 'token', 'plateforme', 'derniere_utilisation_le'])]
class DeviceToken extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $casts = [
        'derniere_utilisation_le' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
