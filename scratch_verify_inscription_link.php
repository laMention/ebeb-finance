<?php
use App\Models\CguAcceptation;
use App\Services\InscriptionService;

$acceptationId = '01a082dc-c73c-7084-9b8f-6bf9972a8655';
$avant = CguAcceptation::find($acceptationId);
echo "avant inscription, user_id: " . json_encode($avant->user_id) . "\n";

$service = new InscriptionService();
$data = [
    'cgu_acceptation_id' => $acceptationId,
    'nom' => 'TEST', 'prenom' => 'CGU VERIF',
    'numero_cnps' => '', 'numero_cmu' => '',
    'situation_familiale' => 'CELIBATAIRE', 'sexe' => 'HOMME',
    'date_naissance' => '1995-01-01',
    'email' => 'test.cgu.verif@yopmail.com',
    'lieu_naissance' => 'ABIDJAN', 'profession' => 'DEV',
    'telephone' => '0700000099',
    'ville' => 'ABIDJAN', 'quartier' => 'COCODY', 'village' => '', 'adresse_postale' => '',
    'categorie_professionnelle' => 'TEST', 'metier' => 'TEST',
    'date_debut_activite' => '2020-01-01',
    'ville_activite' => 'ABIDJAN', 'quartier_activite' => 'COCODY', 'commune_sous_prefecture_activite' => 'COCODY',
    'montant_revenu' => 100000, 'montant_cotisation_regime_base' => 5000,
    'montant_cotisation_regime_complementaire' => 0, 'montant_cotisation_mensuelle' => 5000,
    'montant_cotisation_trimestrielle' => 15000,
    'type_document' => 'CNI', 'numero_document' => 'TESTCGU001',
    'document_etablie_le' => '2020-01-01', 'document_expire_le' => '2030-01-01',
];
$fichiersKyc = ['recto' => null, 'verso' => null, 'selfie' => null];

$user = $service->inscrire($data, $fichiersKyc);
echo "user créé: " . $user->id . "\n";

$apres = CguAcceptation::find($acceptationId);
echo "apres inscription, user_id: " . json_encode($apres->user_id) . " (attendu: " . $user->id . ")\n";
echo "match: " . ($apres->user_id === $user->id ? 'OUI' : 'NON -> PROBLEME') . "\n";

echo "== Nettoyage ==\n";
\App\Models\DocumentKYC::where('user_id', $user->id)->forceDelete();
\App\Models\DeclarationRevenu::where('user_id', $user->id)->forceDelete();
\App\Models\InformationProfessionnelle::where('user_id', $user->id)->forceDelete();
$user->forceDelete();
$apres->forceDelete();
echo "done\n";
