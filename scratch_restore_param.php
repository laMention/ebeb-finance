<?php
use App\Models\ParametreGeneral;
$log = \DB::table('log_audits')
    ->where('action', 'PARAMETRE_GENERAL.UPDATE')
    ->orderByDesc('created_at')
    ->first();
echo "avant (donnees_avant): " . $log->donnees_avant . "\n";
