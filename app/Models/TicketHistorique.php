<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'ticket_id', 'statut_precedent', 'statut_nouveau',
    'severite_precedente', 'severite_nouvelle', 'commentaire',
    'modifie_par', 'ip_adresse',
])]
class TicketHistorique extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType   = 'string';

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function modifiePar(): BelongsTo
    {
        return $this->belongsTo(Administrateur::class, 'modifie_par');
    }
}
