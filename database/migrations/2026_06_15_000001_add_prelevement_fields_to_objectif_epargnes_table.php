<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('objectif_epargnes', function (Blueprint $table) {
            $table->enum('type_calcul', ['FIXE', 'POURCENTAGE'])->nullable()->after('est_actif');
            $table->decimal('valeur', 15, 2)->nullable()->after('type_calcul');
            $table->date('date_limite')->nullable()->change();
            $table->decimal('montant_epargne', 15, 2)->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('objectif_epargnes', function (Blueprint $table) {
            $table->dropColumn(['type_calcul', 'valeur']);
            $table->date('date_limite')->nullable(false)->change();
            $table->decimal('montant_epargne', 15, 2)->default(null)->change();
        });
    }
};
