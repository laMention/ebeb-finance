<?php
use App\Models\User;
$user = User::where('email', 'botchi@yopmail.com')->first();
$user->tokens()->where('name', 'verif-cgu-profil')->delete();
echo "done\n";
