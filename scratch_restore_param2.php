<?php
use App\Models\ParametreGeneral;
ParametreGeneral::where('id', 1)->update(['nom_plateforme' => 'Ebeb finance']);
echo "Restauré: " . ParametreGeneral::find(1)->nom_plateforme . "\n";
