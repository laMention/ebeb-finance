<?php
echo "DB name: " . DB::connection()->getDatabaseName() . "\n";
echo "Total type_cotisations (raw, ignore soft delete): " . DB::table('type_cotisations')->count() . "\n";
$rows = DB::table('type_cotisations')->get(['id','libelle','code','est_obligatoire','est_actif','deleted_at']);
foreach ($rows as $r) echo json_encode($r) . "\n";
