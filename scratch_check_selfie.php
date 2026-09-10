<?php
use App\Models\User;

$user = User::where('nom', 'DAGOU')->orWhere('prenom', 'LIKE', '%BOTCHI%')->first();
if (!$user) $user = User::where('email', 'botchi@yopmail.com')->first();
echo "user: " . ($user?->email) . " / " . $user?->nom . " " . $user?->prenom . "\n";

$doc = $user->documentKYCs()->first();
echo "document KYC: " . ($doc ? 'trouvé' : 'AUCUN') . "\n";
if ($doc) {
    echo "url_selfie brut: " . ($doc->url_selfie ?? 'NULL') . "\n";
    echo "url complète: " . ($doc->url_selfie ? storage_public_path($doc->url_selfie) : 'NULL') . "\n";
}
