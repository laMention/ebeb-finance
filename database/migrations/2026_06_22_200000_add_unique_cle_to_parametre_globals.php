<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Keep only the most recently updated row per cle, remove duplicates
        $cles = DB::table('parametre_globals')->select('cle')->distinct()->pluck('cle');
        foreach ($cles as $cle) {
            $ids = DB::table('parametre_globals')
                ->where('cle', $cle)
                ->orderByDesc('updated_at')
                ->pluck('id');
            if ($ids->count() > 1) {
                DB::table('parametre_globals')
                    ->where('cle', $cle)
                    ->whereNotIn('id', [$ids->first()])
                    ->delete();
            }
        }

        Schema::table('parametre_globals', function (Blueprint $table) {
            $table->unique('cle');
        });
    }

    public function down(): void
    {
        Schema::table('parametre_globals', function (Blueprint $table) {
            $table->dropUnique(['cle']);
        });
    }
};
