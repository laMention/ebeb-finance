<?php
use App\Models\Administrateur;
use App\Models\Cotisation;
use App\Models\Operation;
use App\Models\PaiementEntrant;
use App\Models\Remboursement;
use App\Models\TypeCotisation;
use App\Models\User;
use App\Services\RecapitulatifService;
use App\Services\RemboursementService;

$user = User::where('email', 'botchi@yopmail.com')->first();
$admin = Administrateur::first();
$nsia = TypeCotisation::where('code', 'NSIA')->first() ?? TypeCotisation::whereNotNull('id')->first();
echo "user: {$user->email}, type: {$nsia->libelle} ({$nsia->code}), admin: {$admin->email}\n";

$paiement = PaiementEntrant::create([
    'user_id' => $user->id, 'compte_mobile_money_id' => $user->compteMobileMoneys()->first()->id ?? null,
    'montant_brut' => 10000, 'statut' => 'SUCCES', 'reference_externe' => 'TEST-RMB-' . uniqid(),
    'operateur_source' => 'WAVE',
]);

$dateOriginale = now()->subDays(10);
$opErronee = Operation::create([
    'user_id' => $user->id, 'type_cotisation_id' => $nsia->id, 'paiement_entrant_id' => $paiement->id,
    'montant' => 1000, 'type_operation' => 'COTISATION_PERSONNALISEE', 'statut' => 'SUCCES',
    'reference' => 'TEST-COT-' . uniqid(), 'date_operation' => $dateOriginale,
    'libelle' => "Cotisation {$nsia->libelle}",
]);
echo "Opération erronée créée: {$opErronee->id}, montant=1000, date=" . $dateOriginale->format('Y-m-d') . "\n";

// Simule la ligne de cotisation mensuelle correspondante (comme le ferait enregistrerVersement())
$cotisation = Cotisation::create([
    'user_id' => $user->id, 'type_cotisation_id' => $nsia->id,
    'mois' => $dateOriginale->month, 'annee' => $dateOriginale->year,
    'montant_verse' => 1000, 'montant_objectif' => 5000, 'montant_restant' => 4000,
    'statut' => 'EN_COURS', 'numero_adherent' => $user->reference,
    'date_paiement' => $dateOriginale,
]);
echo "Ligne Cotisation créée (mois={$cotisation->mois}, annee={$cotisation->annee}): verse=1000\n";

$soldeAvant = (new RecapitulatifService())->soldesGlobaux($user);
echo "\nsolde_principal AVANT remboursement: {$soldeAvant['solde_principal']}\n";

$service = app(RemboursementService::class);

echo "\n== verifierEligibilite() ==\n";
$eligibilite = $service->verifierEligibilite($opErronee);
echo json_encode($eligibilite) . "\n";

echo "\n== rembourser() (remboursement partiel : 700 sur 1000) ==\n";
$remboursement = $service->rembourser($opErronee, $admin, 'Cotisation NSIA non configurée par l\'utilisateur', 700.0);
echo "Remboursement créé: {$remboursement->id}\n";
echo "  operation_id (originale): {$remboursement->operation_id}\n";
echo "  operation_remboursement_id: {$remboursement->operation_remboursement_id}\n";
echo "  montant_preleve: {$remboursement->montant_preleve}, montant_rembourse: {$remboursement->montant_rembourse}\n";

$opCredit = Operation::find($remboursement->operation_remboursement_id);
echo "  Operation crédit: type={$opCredit->type_operation}, montant={$opCredit->montant}, statut={$opCredit->statut}, reference={$opCredit->reference}\n";
echo "  Libellé: {$opCredit->libelle}\n";

$soldeApres = (new RecapitulatifService())->soldesGlobaux($user);
echo "\nsolde_principal APRES remboursement: {$soldeApres['solde_principal']} (attendu: avant + 700)\n";
$diff = bcsub($soldeApres['solde_principal'], $soldeAvant['solde_principal'], 2);
echo "Différence: $diff (attendu: 700.00)\n";

$cotisationApres = $cotisation->fresh();
echo "\nCotisation apres reversement: montant_verse={$cotisationApres->montant_verse} (attendu: 300 = 1000-700), statut={$cotisationApres->statut}\n";

echo "\n== Contrôles négatifs ==\n";
try {
    $service->rembourser($opErronee, $admin, 'second essai');
    echo "ERREUR: un second remboursement n'a PAS été rejeté !\n";
} catch (\RuntimeException $e) {
    echo "Second remboursement rejeté (attendu): {$e->getMessage()}\n";
}

$opEnAttente = Operation::create([
    'user_id' => $user->id, 'type_cotisation_id' => $nsia->id, 'paiement_entrant_id' => $paiement->id,
    'montant' => 500, 'type_operation' => 'COTISATION_PERSONNALISEE', 'statut' => 'EN_ATTENTE',
    'reference' => 'TEST-PEND-' . uniqid(), 'date_operation' => now(),
]);
try {
    $service->rembourser($opEnAttente, $admin, 'test statut invalide');
    echo "ERREUR: remboursement d'une opération EN_ATTENTE non rejeté !\n";
} catch (\RuntimeException $e) {
    echo "Opération EN_ATTENTE rejetée (attendu): {$e->getMessage()}\n";
}

$opAutre = Operation::create([
    'user_id' => $user->id, 'type_cotisation_id' => $nsia->id, 'paiement_entrant_id' => $paiement->id,
    'montant' => 500, 'type_operation' => 'COTISATION_PERSONNALISEE', 'statut' => 'SUCCES',
    'reference' => 'TEST-MTX-' . uniqid(), 'date_operation' => now(),
]);
try {
    $service->rembourser($opAutre, $admin, 'test montant excessif', 999.0);
    echo "ERREUR: montant > prélevé non rejeté !\n";
} catch (\RuntimeException $e) {
    echo "Montant excessif rejeté (attendu): {$e->getMessage()}\n";
}

echo "\n== Validation FormRequest (motif) ==\n";
$rules = ['motif' => ['required','string','min:5'], 'montant' => ['nullable','numeric','min:0.01']];
$v1 = \Illuminate\Support\Facades\Validator::make([], $rules);
echo "motif vide rejeté: " . ($v1->fails() ? 'OUI (attendu)' : 'NON -> PROBLEME') . "\n";

echo "\n== Nettoyage ==\n";
Remboursement::where('operation_id', $opErronee->id)->forceDelete();
Operation::where('id', $opCredit->id)->forceDelete();
Operation::where('id', $opErronee->id)->forceDelete();
Operation::where('id', $opEnAttente->id)->forceDelete();
Operation::where('id', $opAutre->id)->forceDelete();
Cotisation::where('id', $cotisation->id)->forceDelete();
PaiementEntrant::where('id', $paiement->id)->forceDelete();
echo "done\n";
