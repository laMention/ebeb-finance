<?php
use App\Models\ReglePrelevement;
use App\Models\TypeCotisation;

$userB = '01a057d8-3a19-722d-a227-0b07d5b762cf';
$nsia = TypeCotisation::where('code', 'NSIA_ASSUR')->where('user_id', $userB)->first();

echo "Type NSIA: " . $nsia->id . "\n";
$existe = ReglePrelevement::where('user_id', $userB)->where('type_cotisation_id', $nsia->id)->first();
echo "Regle actuelle: " . ($existe ? json_encode($existe) : 'ABSENTE (confirmé supprimée par erreur)') . "\n";

if (!$existe) {
    $recreee = ReglePrelevement::create([
        'user_id' => $userB,
        'type_cotisation_id' => $nsia->id,
        'type_calcul' => 'POURCENTAGE',
        'valeur' => 5,
        'est_actif' => true,
        'ordre_priorite' => 4,
    ]);
    echo "Regle restaurée: " . $recreee->id . " (valeurs d'origine reconstituées : POURCENTAGE, 5%, actif, ordre 4)\n";
} else {
    echo "Rien à faire, la règle existe déjà.\n";
}
