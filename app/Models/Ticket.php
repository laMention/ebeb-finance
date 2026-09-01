<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable([
    'reference', 'user_id', 'objet', 'description', 'severite', 'statut',
    'source', 'resolu_le', 'traite_par',
])]
class Ticket extends Model
{
    use HasUuids, SoftDeletes;

    public $incrementing = false;
    protected $keyType   = 'string';

    protected $casts = [
        'resolu_le' => 'datetime',
    ];

    public static array $SEVERITES = ['FAIBLE', 'NORMALE', 'HAUTE', 'CRITIQUE'];
    public static array $STATUTS   = ['OUVERT', 'EN_COURS', 'RESOLU', 'FERME'];

    public static function genererReference(): string
    {
        return 'TCK-' . now()->format('ymd') . '-' . strtoupper(Str::random(6));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function traitePar(): BelongsTo
    {
        return $this->belongsTo(Administrateur::class, 'traite_par');
    }

    public function historique(): HasMany
    {
        return $this->hasMany(TicketHistorique::class)->latest();
    }
}
