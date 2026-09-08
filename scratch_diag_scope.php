<?php
echo "DB: " . DB::connection()->getDatabaseName() . "\n";
foreach (['type_cotisations','partenaires_financiers','regle_prelevements','users','cotisations'] as $t) {
    echo "$t: " . DB::table($t)->count() . "\n";
}
echo "env: " . app()->environment() . "\n";
echo "DB_DATABASE config: " . config('database.connections.mysql.database') . "\n";
