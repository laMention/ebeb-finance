<?php
use App\Models\Administrateur;
$admin = Administrateur::first();
$admin->tokens()->where('name', 'verif-param')->delete();
echo "done\n";
