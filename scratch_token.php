<?php
use App\Models\User;

$user = User::where('email', 'botchi@yopmail.com')->first();
echo "statut: " . $user->statut . "\n";
$token = $user->createToken('verif-epargne')->plainTextToken;
echo "TOKEN:" . $token . "\n";
