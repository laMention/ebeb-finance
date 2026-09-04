<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'expediteur_id',
    'destinataire_id',
    'compte_mobile_money_expediteur_id',
    'compte_mobile_money_destinataire_id',
    'operateur',
    'montant',
    'reference',
    'statut',
])]
class TransfertQr extends Model
{
    use HasUuids;

    protected $table = 'transferts_qr';
    public $incrementing = false;
    protected $keyType = 'string';

    public function expediteur()
    {
        return $this->belongsTo(User::class, 'expediteur_id');
    }

    public function destinataire()
    {
        return $this->belongsTo(User::class, 'destinataire_id');
    }

    public function compteMobileMoneyExpediteur()
    {
        return $this->belongsTo(CompteMobileMoney::class, 'compte_mobile_money_expediteur_id');
    }

    public function compteMobileMoneyDestinataire()
    {
        return $this->belongsTo(CompteMobileMoney::class, 'compte_mobile_money_destinataire_id');
    }
}
