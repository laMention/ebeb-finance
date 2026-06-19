<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('compte_mobile_moneys', function (Blueprint $table) {
            $table->enum('statut', ['ACTIF', 'EN_ATTENTE', 'SUSPENDU'])->default('ACTIF')->after('est_actif');
        });

        // Synchroniser le statut des enregistrements existants à partir de est_actif
        \DB::table('compte_mobile_moneys')
            ->where('est_actif', false)
            ->whereNull('deleted_at')
            ->update(['statut' => 'EN_ATTENTE']);
    }

    public function down(): void
    {
        Schema::table('compte_mobile_moneys', function (Blueprint $table) {
            $table->dropColumn('statut');
        });
    }
};
