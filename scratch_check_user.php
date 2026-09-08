<?php
$u = DB::table('users')->first();
echo json_encode($u) . "\n";
