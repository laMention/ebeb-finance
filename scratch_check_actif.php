<?php
use App\Models\TypeCotisation;
$inactifs = TypeCotisation::whereNotNull('user_id')->where('est_actif', false)->get(['id','libelle','user_id','est_actif']);
echo "Types personnels inactifs: " . $inactifs->count() . "\n";
foreach ($inactifs as $t) echo json_encode($t) . "\n";
