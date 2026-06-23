<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reversements', function (Blueprint $table) {
            $table->date('periode_debut')->nullable()->after('date_reversement');
            $table->date('periode_fin')->nullable()->after('periode_debut');
            $table->dateTime('date_execution')->nullable()->after('periode_fin');
            $table->text('motif_annulation')->nullable()->after('initie_par');
            $table->string('annule_par')->nullable()->after('motif_annulation');
        });
    }

    public function down(): void
    {
        Schema::table('reversements', function (Blueprint $table) {
            $table->dropColumn(['periode_debut', 'periode_fin', 'date_execution', 'motif_annulation', 'annule_par']);
        });
    }
};
