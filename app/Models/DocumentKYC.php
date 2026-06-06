<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['user_id', 'type_document', 'numero_document', 'document_etablie_le', 'document_expire_le', 'url_recto', 'url_verso', 'url_selfie', 'statut', 'motif_rejet', 'valide_par', 'validated_at'])]
class DocumentKYC extends Model
{
    //
    use SoftDeletes,HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    // url pour lire les documents KYC
    public function getUrlRectoAttribute($value)
    {
        return $value ? asset('storage/' . $value) : null;
    }

    public function getUrlVersoAttribute($value)
    {
        return $value ? asset('storage/' . $value) : null;
    }

    public function getUrlSelfieAttribute($value)
    {
        return $value ? asset('storage/' . $value) : null;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
