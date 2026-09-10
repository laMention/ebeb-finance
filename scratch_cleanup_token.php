<?php
use App\Models\User;
$user = User::find('01a08227-372e-724b-b849-e68440001aad');
$user->tokens()->where('name', 'verif-recap')->delete();
echo "done\n";
