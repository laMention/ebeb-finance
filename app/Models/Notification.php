<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['user_id', 'canal', 'type', 'contenu', 'est_envoye', 'envoye_le'])]
class Notification extends Model
{
    //
    use SoftDeletes,HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $casts = [
        'est_envoye' => 'boolean',
        'envoye_le' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
