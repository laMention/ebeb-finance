<?php
use App\Models\TypeCotisation;
use App\Models\User;
use App\Services\ReglePrelevementService;

$userA = User::where('email', 'elyseebotchi@yopmail.com')->first();
$userB = User::find('01a057d8-3a19-722d-a227-0b07d5b762cf');
$service = new ReglePrelevementService();

echo "== 1) Vue de User B : ses propres types persos historiques doivent être disponibles ==\n";
$vueB = $service->obtenirTypeCotisationsAvecRegles($userB->id);
foreach (['NSIA_ASSUR', 'SALAM'] as $code) {
    $ligne = collect($vueB['data'])->firstWhere('code', $code);
    echo "$code: " . ($ligne ? ('present, regle=' . json_encode($ligne['regle'])) : 'ABSENT -> PROBLEME') . "\n";
}

echo "\n== 2) Type perso créé par User A doit être visible pour User B (catalogue commun) ==\n";
$typePersoA = TypeCotisation::create([
    'libelle' => 'TYPE PERSO USER A (verif catalogue commun)',
    'code' => 'PERSO_A_TEST',
    'categorie' => 'PARTENAIRE',
    'est_actif' => true,
    'est_obligatoire' => false,
    'user_id' => $userA->id,
]);
$vueB2 = $service->obtenirTypeCotisationsAvecRegles($userB->id);
$ligne2 = collect($vueB2['data'])->firstWhere('id', $typePersoA->id);
echo "Visible pour User B: " . ($ligne2 && $ligne2['regle'] === null ? 'OUI (attendu)' : 'NON -> PROBLEME') . "\n";

echo "\n== 3) User B configure NSIA -> doit disparaitre de SA liste dispo, rester dispo pour User A ==\n";
$nsia = TypeCotisation::where('code', 'NSIA_ASSUR')->where('user_id', $userB->id)->first();
$res = $service->sauvegarderRegle($userB->id, [
    'type_cotisation_id' => $nsia->id,
    'type_calcul' => 'POURCENTAGE',
    'valeur' => 2,
    'est_actif' => true,
]);
echo "config: " . json_encode(['success' => $res['success']]) . "\n";

$vueB3 = $service->obtenirTypeCotisationsAvecRegles($userB->id);
$ligneB3 = collect($vueB3['data'])->firstWhere('id', $nsia->id);
echo "User B, apres config, regle: " . json_encode($ligneB3['regle']) . " (attendu non nul)\n";

$vueA3 = $service->obtenirTypeCotisationsAvecRegles($userA->id);
$ligneA3 = collect($vueA3['data'])->firstWhere('id', $nsia->id);
echo "User A voit NSIA avec regle: " . json_encode($ligneA3['regle']) . " (attendu null -> reste dispo pour lui)\n";

echo "\n== Nettoyage ==\n";
\App\Models\ReglePrelevement::withTrashed()->where('user_id', $userB->id)->where('type_cotisation_id', $nsia->id)->forceDelete();
$typePersoA->forceDelete();
echo "done\n";
