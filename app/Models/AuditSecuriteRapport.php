<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditSecuriteRapport extends Model
{
    protected $table = 'audit_securite_rapports';

    protected $fillable = [
        'titre', 'version', 'statut',
        'nb_critique', 'nb_eleve', 'nb_moyen', 'nb_faible', 'nb_info', 'nb_corrige',
        'notes', 'realise_par', 'date_audit',
    ];

    protected $casts = [
        'date_audit' => 'datetime',
    ];

    public function vulnerabilites(): HasMany
    {
        return $this->hasMany(AuditSecuriteVulnerabilite::class, 'rapport_id');
    }

    public function realisePar(): BelongsTo
    {
        return $this->belongsTo(Administrateur::class, 'realise_par');
    }

    public function recalculerCompteurs(): void
    {
        $counts = $this->vulnerabilites()
            ->selectRaw('criticite, count(*) as total')
            ->groupBy('criticite')
            ->pluck('total', 'criticite');

        $this->update([
            'nb_critique' => $counts['CRITIQUE'] ?? 0,
            'nb_eleve'    => $counts['ELEVE']    ?? 0,
            'nb_moyen'    => $counts['MOYEN']    ?? 0,
            'nb_faible'   => $counts['FAIBLE']   ?? 0,
            'nb_info'     => $counts['INFO']     ?? 0,
            'nb_corrige'  => $this->vulnerabilites()->where('statut', 'CORRIGE')->count(),
        ]);
    }
}
