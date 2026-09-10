<?php
use App\Models\Administrateur;
$admin = Administrateur::first();
echo "admin: " . ($admin?->email) . "\n";
if ($admin) {
    $token = $admin->createToken('verif-param')->plainTextToken;
    echo "TOKEN:" . $token . "\n";
}
