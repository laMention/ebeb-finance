<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditSecuriteVulnerabilite extends Model
{
    protected $table = 'audit_securite_vulnerabilites';

    protected $fillable = [
        'rapport_id', 'code', 'titre', 'criticite', 'statut', 'categorie',
        'description', 'impact', 'recommandation', 'fichier', 'ligne',
        'date_detection', 'date_correction', 'corrige_par', 'notes_correction',
    ];

    protected $casts = [
        'date_detection' => 'datetime',
        'date_correction' => 'datetime',
    ];

    public function rapport(): BelongsTo
    {
        return $this->belongsTo(AuditSecuriteRapport::class, 'rapport_id');
    }

    public function corrigePar(): BelongsTo
    {
        return $this->belongsTo(Administrateur::class, 'corrige_par');
    }
}
