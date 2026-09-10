<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['cgu_version_id', 'user_id', 'accepte_le', 'ip_adresse', 'user_agent'])]
class CguAcceptation extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $casts = [
        'accepte_le' => 'datetime',
    ];

    public function version()
    {
        return $this->belongsTo(CguVersion::class, 'cgu_version_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
