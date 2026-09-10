<?php
use App\Models\User;
$user = User::find('01a08227-372e-724b-b849-e68440001aad');
$token = $user->createToken('verif-recap')->plainTextToken;
echo "TOKEN:" . $token . "\n";
