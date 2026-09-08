<?php
use App\Models\TypeCotisation;
use App\Models\User;
use App\Services\ReglePrelevementService;

$axaPartenaireId = '01a080c4-312a-7153-924c-da1c4d05d5d6'; // Axa assurance, actif
$user = User::where('email', 'elyseebotchi@yopmail.com')->first();
$service = new ReglePrelevementService();

echo "== Avant : liste des types (attendu AXA absent des 'disponibles') ==\n";
$avant = $service->obtenirTypeCotisationsAvecRegles($user->id);
$disponiblesAvant = collect($avant['data'])->filter(fn($t) =>
    !$t['user_id'] ?? true // placeholder, on va juste chercher le code AXA_GLOBAL_TEST
);

echo "== Création d'un type global de test rattaché à Axa ==\n";
$type = TypeCotisation::create([
    'libelle' => 'AXA GLOBAL TEST (vérif refonte)',
    'code' => 'AXA_GLOBAL_TEST',
    'categorie' => 'PARTENAIRE', // volontairement différent de 'PERSONNALISEE' pour prouver que le filtre ne dépend plus de ce texte
    'est_actif' => true,
    'est_obligatoire' => false,
    'user_id' => null,
    'partenaire_id' => $axaPartenaireId,
    'default_type_calcul' => 'POURCENTAGE',
    'default_valeur' => 3,
]);
echo "type créé: " . $type->id . "\n";

echo "== Après création : doit apparaître dans 'disponibles' ==\n";
$apres = $service->obtenirTypeCotisationsAvecRegles($user->id);
$ligneApres = collect($apres['data'])->firstWhere('id', $type->id);
echo json_encode($ligneApres) . "\n";
$estDisponible = $ligneApres
    && !($ligneApres['est_obligatoire'])
    && !$ligneApres['est_personnalise']
    && $ligneApres['partenaire_id'] === $axaPartenaireId
    && $ligneApres['partenaire_actif'] === true
    && $ligneApres['regle'] === null;
echo "Disponible pour ajout (logique équivalente au mobile): " . ($estDisponible ? 'OUI' : 'NON -> PROBLEME') . "\n";

echo "== Configuration de la règle (l'utilisateur 'ajoute' la cotisation) ==\n";
$config = $service->sauvegarderRegle($user->id, [
    'type_cotisation_id' => $type->id,
    'type_calcul' => 'POURCENTAGE',
    'valeur' => 3,
    'est_actif' => true,
]);
echo json_encode(['success' => $config['success']]) . "\n";

echo "== Après configuration : doit disparaître de 'disponibles', apparaître comme configuré ==\n";
$apresConfig = $service->obtenirTypeCotisationsAvecRegles($user->id);
$ligneConfig = collect($apresConfig['data'])->firstWhere('id', $type->id);
echo json_encode($ligneConfig) . "\n";
$plusDisponible = $ligneConfig && $ligneConfig['regle'] !== null;
echo "Bien configuré (regle non nulle): " . ($plusDisponible ? 'OUI' : 'NON -> PROBLEME') . "\n";

echo "== Nettoyage ==\n";
\App\Models\ReglePrelevement::withTrashed()->where('user_id', $user->id)->where('type_cotisation_id', $type->id)->forceDelete();
$type->forceDelete();
echo "done\n";
