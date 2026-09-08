<?php
$idx = DB::select("SHOW INDEX FROM type_cotisations");
foreach ($idx as $i) echo json_encode($i) . "\n";
