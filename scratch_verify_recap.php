<?php
use App\Models\User;
use App\Services\RecapitulatifService;

$user = User::find('01a08227-372e-724b-b849-e68440001aad');
echo "user: " . ($user ? $user->email : 'INTROUVABLE (test avec un autre compte)') . "\n";

if (!$user) {
    $user = User::first();
    echo "utilisation de: " . $user->email . "\n";
}

$service = new RecapitulatifService();

echo "== recapitulatif() ==\n";
$recap = $service->recapitulatif($user, ['mois' => now()->month, 'annee' => now()->year]);
echo "OK - total_objectif_mensuel: " . $recap['total_objectif_mensuel'] . "\n";

echo "== soldesGlobaux() ==\n";
$soldes = $service->soldesGlobaux($user);
echo "OK - solde_principal: " . $soldes['solde_principal'] . ", solde_epargne: " . $soldes['solde_epargne'] . "\n";
