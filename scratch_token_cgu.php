<?php
use App\Models\User;
$user = User::where('email', 'botchi@yopmail.com')->first();
$token = $user->createToken('verif-cgu-profil')->plainTextToken;
echo "TOKEN:" . $token . "\n";
