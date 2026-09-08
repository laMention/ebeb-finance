<?php
use App\Models\TypeCotisation;
use App\Models\User;
use App\Services\ReglePrelevementService;

$axaPartenaireId = '01a080c4-312a-7153-924c-da1c4d05d5d6';
$userA = User::where('email', 'elyseebotchi@yopmail.com')->first();
$userB = User::find('01a057d8-3a19-722d-a227-0b07d5b762cf'); // propriétaire de NSIA/SALAM (autre compte réel)

if (!$userA || !$userB) {
    echo "ERREUR: utilisateurs de test introuvables (A=" . ($userA?->id) . " B=" . ($userB?->id) . ")\n";
    exit(1);
}
echo "userA: {$userA->id} ({$userA->email})\n";
echo "userB: {$userB->id} ({$userB->email})\n";

$service = new ReglePrelevementService();

echo "== Création d'un type global de test rattaché à Axa ==\n";
$type = TypeCotisation::create([
    'libelle' => 'AXA SCOPE TEST',
    'code' => 'AXA_SCOPE_TEST',
    'categorie' => 'PARTENAIRE',
    'est_actif' => true,
    'est_obligatoire' => false,
    'user_id' => null,
    'partenaire_id' => $axaPartenaireId,
]);
echo "type: {$type->id}\n";

echo "== User A configure ce type ==\n";
$res = $service->sauvegarderRegle($userA->id, [
    'type_cotisation_id' => $type->id,
    'type_calcul' => 'POURCENTAGE',
    'valeur' => 4,
    'est_actif' => true,
]);
echo json_encode(['success' => $res['success']]) . "\n";

echo "== Vue de User A : regle doit être NON NULLE (donc masqué du select) ==\n";
$vueA = $service->obtenirTypeCotisationsAvecRegles($userA->id);
$ligneA = collect($vueA['data'])->firstWhere('id', $type->id);
echo "User A - regle: " . ($ligneA['regle'] !== null ? 'PRESENTE (attendu)' : 'ABSENTE -> PROBLEME') . "\n";

echo "== Vue de User B (n'a rien configuré) : regle doit être NULLE (donc visible dans le select) ==\n";
$vueB = $service->obtenirTypeCotisationsAvecRegles($userB->id);
$ligneB = collect($vueB['data'])->firstWhere('id', $type->id);
echo "User B - regle: " . ($ligneB === null ? 'TYPE ABSENT DE LA LISTE -> PROBLEME' : json_encode($ligneB['regle'])) . "\n";
echo "User B voit le type comme disponible (regle null): " . (($ligneB && $ligneB['regle'] === null) ? 'OUI (attendu, scoping correct)' : 'NON -> PROBLEME DE SCOPING GLOBAL') . "\n";

echo "== Nettoyage ==\n";
\App\Models\ReglePrelevement::withTrashed()->where('user_id', $userA->id)->where('type_cotisation_id', $type->id)->forceDelete();
$type->forceDelete();
echo "done\n";
