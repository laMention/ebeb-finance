<?php
use App\Models\ObjectifEpargne;
use App\Models\User;

ObjectifEpargne::where('id', '01a0822c-6ea3-73d6-8749-b4933f1d7fc9')->forceDelete();
$user = User::where('email', 'botchi@yopmail.com')->first();
$user->tokens()->where('name', 'verif-epargne')->delete();
echo "Nettoyage effectué\n";
