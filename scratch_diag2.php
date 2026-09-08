<?php
use App\Models\TypeCotisation;

echo "== TOUS les type_cotisations (y compris user_id non null / inactifs) ==\n";
$types = TypeCotisation::withTrashed()->get(['id','libelle','code','categorie','est_actif','est_obligatoire','partenaire_id','user_id','deleted_at']);
foreach ($types as $t) {
    echo json_encode($t) . "\n";
}
