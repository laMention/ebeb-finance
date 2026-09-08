<?php
use App\Models\TypeCotisation;
use App\Models\User;
use App\Services\ReglePrelevementService;

$axaPartenaireId = '01a080c4-312a-7153-924c-da1c4d05d5d6';
$user = User::where('email', 'elyseebotchi@yopmail.com')->first();
$service = new ReglePrelevementService();

echo "== Création type global de test ==\n";
$type = TypeCotisation::create([
    'libelle' => 'AXA UNIFIE TEST (verif formulaire unique)',
    'code' => 'AXA_UNIFIE_TEST',
    'categorie' => 'PARTENAIRE',
    'est_actif' => true,
    'est_obligatoire' => false,
    'user_id' => null,
    'partenaire_id' => $axaPartenaireId,
]);
echo "type: " . $type->id . "\n";

echo "== Simule le nouveau formulaire unique : select + config en un seul appel ==\n";
$res = $service->sauvegarderRegle($user->id, [
    'type_cotisation_id' => $type->id,
    'type_calcul' => 'POURCENTAGE',
    'valeur' => 4,
    'est_actif' => true,
]);
echo json_encode(['success' => $res['success'], 'created' => $res['created'] ?? null]) . "\n";

$apres = $service->obtenirTypeCotisationsAvecRegles($user->id);
$ligne = collect($apres['data'])->firstWhere('id', $type->id);
echo "regle non nulle apres enregistrement unique: " . ($ligne['regle'] !== null ? 'OUI' : 'NON -> PROBLEME') . "\n";

echo "== Nettoyage ==\n";
\App\Models\ReglePrelevement::withTrashed()->where('user_id', $user->id)->where('type_cotisation_id', $type->id)->forceDelete();
$type->forceDelete();
echo "done\n";
