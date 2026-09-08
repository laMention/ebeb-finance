<?php
use App\Models\TypeCotisation;

echo "== withTrashed, tous les non-obligatoires ==\n";
$all = TypeCotisation::withTrashed()->where('est_obligatoire', false)->get(['id','libelle','code','user_id','est_actif','deleted_at']);
foreach ($all as $t) echo json_encode($t) . "\n";
