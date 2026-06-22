<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationLog extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType   = 'string';

    protected $fillable = [
        'canal', 'type_notification', 'destinataire', 'sujet',
        'contenu', 'statut', 'message_erreur', 'tentatives', 'envoye_a', 'user_id',
    ];

    protected $casts = [
        'envoye_a'   => 'datetime',
        'tentatives' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
