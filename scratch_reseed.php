<?php
use App\Models\TypeCotisation;
use App\Models\PartenairesFinancier;

$cnpsPartenaireId = PartenairesFinancier::where('type', 'CNPS')->value('id');
$axaPartenaireId = PartenairesFinancier::where('nom', 'Axa assurance')->value('id');

echo "Partenaire CNPS: " . ($cnpsPartenaireId ?? 'INTROUVABLE') . "\n";
echo "Partenaire Axa: " . ($axaPartenaireId ?? 'INTROUVABLE') . "\n";

$cnps = TypeCotisation::firstOrCreate(
    ['code' => 'CNPS'],
    [
        'libelle' => 'CAISSE NATIONALE DE PRÉVOYANCE SOCIALE',
        'categorie' => 'CNPS',
        'est_actif' => true,
        'est_obligatoire' => true,
        'partenaire_id' => $cnpsPartenaireId,
        'default_type_calcul' => 'POURCENTAGE',
        'default_valeur' => 5,
        'default_est_actif' => true,
    ]
);
echo "CNPS: " . $cnps->id . "\n";

$amu = TypeCotisation::firstOrCreate(
    ['code' => 'AMU'],
    [
        'libelle' => 'ASSURANCE MALADIE UNIVERSELLE',
        'categorie' => 'AMU',
        'est_actif' => true,
        'est_obligatoire' => true,
        'partenaire_id' => null,
        'default_type_calcul' => 'POURCENTAGE',
        'default_valeur' => 5,
        'default_est_actif' => true,
    ]
);
echo "AMU: " . $amu->id . "\n";

$axa = TypeCotisation::firstOrCreate(
    ['code' => 'AXA'],
    [
        'libelle' => 'AXA ASSURANCE',
        'categorie' => 'PARTENAIRE',
        'est_actif' => true,
        'est_obligatoire' => false,
        'partenaire_id' => $axaPartenaireId,
        'default_type_calcul' => 'POURCENTAGE',
        'default_valeur' => 3,
        'default_est_actif' => true,
    ]
);
echo "AXA: " . $axa->id . "\n";
