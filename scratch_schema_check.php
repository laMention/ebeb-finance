<?php
$cols = collect(DB::select('SHOW COLUMNS FROM type_cotisations'))->pluck('Field');
echo "Colonnes actuelles de type_cotisations:\n";
foreach ($cols as $c) echo "- $c\n";
echo "\nuser_id present: " . ($cols->contains('user_id') ? 'OUI' : 'NON (confirmé retiré)') . "\n";
