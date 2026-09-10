<?php
use App\Models\ReglePrelevement;
use App\Models\User;
use App\Services\PaiementService;

$user = User::where('email', 'botchi@yopmail.com')->first();
$regles = ReglePrelevement::where('user_id', $user->id)->where('est_actif', true)->get();

echo "== Sauvegarde config actuelle ==\n";
$sauvegarde = $regles->map(fn($r) => ['id' => $r->id, 'type_calcul' => $r->type_calcul, 'valeur' => $r->valeur])->all();
echo json_encode($sauvegarde) . "\n";

echo "\n== Bascule temporaire en FIXE avec des montants qui dépassent un petit paiement ==\n";
foreach ($regles as $r) {
    $r->update(['type_calcul' => 'FIXE', 'valeur' => 2000]); // 3 regles x 2000 = 6000, > paiement de 1500
}

$service = app(PaiementService::class);
$refChargerConfig = new ReflectionMethod(PaiementService::class, 'chargerConfig');
$refChargerConfig->setAccessible(true);
$config = $refChargerConfig->invoke($service, $user->fresh());

$refCalculer = new ReflectionMethod(PaiementService::class, 'calculerRepartition');
$refCalculer->setAccessible(true);
$repartition = $refCalculer->invoke($service, '1500.00', $config);

echo "\n== Répartition pour un paiement de 1500 FCFA (montants fixes 3x2000 dépassent) ==\n";
foreach ($repartition['cotisations'] as $c) {
    echo "- {$c['type_cotisation']->libelle}: {$c['montant']} FCFA\n";
}
echo "montants_fixes_ignores: " . implode(', ', array_map(fn($t) => $t->libelle, $repartition['montants_fixes_ignores'])) . "\n";
$aNsia = collect($repartition['cotisations'])->contains(fn($c) => str_contains(strtoupper($c['type_cotisation']->libelle), 'NSIA'));
echo "NSIA present malgre le fallback: " . ($aNsia ? 'OUI -> PROBLEME' : 'NON (attendu, NSIA jamais configuré)') . "\n";

echo "\n== Restauration de la configuration d'origine ==\n";
foreach ($sauvegarde as $s) {
    ReglePrelevement::where('id', $s['id'])->update(['type_calcul' => $s['type_calcul'], 'valeur' => $s['valeur']]);
}
$verif = ReglePrelevement::where('user_id', $user->id)->where('est_actif', true)->get(['id','type_calcul','valeur']);
echo "Etat restaure: " . json_encode($verif) . "\n";
