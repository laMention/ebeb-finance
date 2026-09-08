<?php
use App\Models\TypeCotisation;
use App\Models\PartenairesFinancier;

echo "== Tous les type_cotisations globaux actifs (user_id NULL) ==\n";
$types = TypeCotisation::whereNull('user_id')->get(['id','libelle','code','categorie','est_actif','est_obligatoire','partenaire_id']);
foreach ($types as $t) {
    echo json_encode($t) . "\n";
}

echo "\n== Tous les partenaires ==\n";
$partenaires = PartenairesFinancier::all(['id','nom','type','est_actif']);
foreach ($partenaires as $p) {
    echo json_encode($p) . "\n";
}
