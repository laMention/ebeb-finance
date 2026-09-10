<?php
use App\Models\CguVersion;

CguVersion::where('numero_version', '>', 1)->delete();
$v1 = CguVersion::where('numero_version', 1)->first();
$v1->update(['est_active' => true]);
echo "Nettoyage effectué. Versions restantes: " . CguVersion::count() . "\n";
echo "v1 active: " . json_encode($v1->fresh()->est_active) . "\n";
