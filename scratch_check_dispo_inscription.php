<?php
use App\Models\TypeCotisation;

$dispo = TypeCotisation::where('est_actif', true)->where('est_obligatoire', false)->get(['id','libelle','code','user_id']);
echo "Types non obligatoires actifs (visibles pour un nouvel utilisateur, 0 regle) : " . $dispo->count() . "\n";
foreach ($dispo as $t) echo "- {$t->libelle} ({$t->code}) - user_id: " . ($t->user_id ?? 'NULL (admin)') . "\n";
