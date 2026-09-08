<?php
use App\Models\User;
use App\Services\ReglePrelevementService;

$user = User::where('email', 'botchi@yopmail.com')->first();
$service = new ReglePrelevementService();
$res = $service->obtenirTypeCotisationsAvecRegles($user->id);
echo "success: " . json_encode($res['success']) . ", total_types: " . $res['total_types'] . "\n";
foreach ($res['data'] as $t) echo "- {$t['libelle']} regle=" . json_encode($t['regle']) . "\n";
