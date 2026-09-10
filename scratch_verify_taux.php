<?php
use App\Models\User;
use App\Services\ReglePrelevementService;

$user = User::where('email', 'botchi@yopmail.com')->first();
$service = new ReglePrelevementService();
$res = $service->obtenirTypeCotisationsAvecRegles($user->id);

foreach ($res['data'] as $t) {
    $configure = $t['regle'] !== null ? 'CONFIGURE' : 'non configuré';
    echo "{$t['libelle']} ({$t['code']}): $configure\n";
}

$attenduDansVosTaux = collect($res['data'])->filter(fn($t) => $t['regle'] !== null)->pluck('libelle');
echo "\nDoit apparaître dans 'Vos taux': " . $attenduDansVosTaux->implode(', ') . "\n";
