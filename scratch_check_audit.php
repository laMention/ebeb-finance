<?php
$logs = \DB::table('log_audits')->where('action', 'REMBOURSEMENT.CREATE')->get();
echo "Lignes trouvées: " . $logs->count() . "\n";
foreach ($logs as $l) echo json_encode($l) . "\n";
