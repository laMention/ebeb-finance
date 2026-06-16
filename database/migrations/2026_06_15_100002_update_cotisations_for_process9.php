<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cotisations', function (Blueprint $table) {
            $table->decimal('montant_restant', 15, 2)->default(0)->after('montant_objectif');
            $table->date('date_debut')->nullable()->after('numero_adherent');
            $table->date('date_fin')->nullable()->after('date_debut');
        });

        // Map old statut values before changing the enum
        DB::statement("UPDATE cotisations SET statut = 'NON_A_JOUR' WHERE statut = 'EN_RETARD'");
        DB::statement("UPDATE cotisations SET statut = 'EN_COURS'   WHERE statut = 'PARTIEL'");

        DB::statement("ALTER TABLE cotisations MODIFY COLUMN statut ENUM(
            'EN_COURS','A_JOUR','NON_A_JOUR','OBJECTIF_ATTEINT','REPORT'
        ) NOT NULL DEFAULT 'EN_COURS'");
    }

    public function down(): void
    {
        DB::statement("UPDATE cotisations SET statut = 'EN_RETARD' WHERE statut = 'NON_A_JOUR'");
        DB::statement("UPDATE cotisations SET statut = 'PARTIEL'   WHERE statut IN ('EN_COURS','OBJECTIF_ATTEINT','REPORT')");

        DB::statement("ALTER TABLE cotisations MODIFY COLUMN statut ENUM(
            'A_JOUR','EN_RETARD','PARTIEL'
        ) NOT NULL");

        Schema::table('cotisations', function (Blueprint $table) {
            $table->dropColumn(['montant_restant', 'date_debut', 'date_fin']);
        });
    }
};
