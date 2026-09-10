<?php
use App\Models\User;
use App\Services\PaiementService;

$user = User::where('email', 'botchi@yopmail.com')->first();
$service = app(PaiementService::class);

$refChargerConfig = new ReflectionMethod(PaiementService::class, 'chargerConfig');
$refChargerConfig->setAccessible(true);
$config = $refChargerConfig->invoke($service, $user);

echo "== regles_prelevements (actives, doit inclure CNPS/AMU/AXA, jamais NSIA) ==\n";
foreach ($config['regles_prelevements'] as $r) {
    echo "- {$r->type_cotisation->libelle} ({$r->type_cotisation->code}) type_calcul={$r->type_calcul} valeur={$r->valeur}\n";
}
echo "'type_cotisations' encore présent dans config: " . (array_key_exists('type_cotisations', $config) ? 'OUI -> PROBLEME' : 'NON (attendu)') . "\n";

$refCalculer = new ReflectionMethod(PaiementService::class, 'calculerRepartition');
$refCalculer->setAccessible(true);
$repartition = $refCalculer->invoke($service, '10000.00', $config);

echo "\n== Répartition pour un paiement de 10000 FCFA ==\n";
foreach ($repartition['cotisations'] as $c) {
    echo "- {$c['type_cotisation']->libelle}: {$c['montant']} FCFA\n";
}
$aNsia = collect($repartition['cotisations'])->contains(fn($c) => str_contains(strtoupper($c['type_cotisation']->libelle), 'NSIA'));
echo "\nNSIA present dans la repartition: " . ($aNsia ? 'OUI -> BUG TOUJOURS PRESENT' : 'NON (corrige)') . "\n";
echo "total_cotisations: {$repartition['total_cotisations']}, montant_net: {$repartition['montant_net']}\n";
