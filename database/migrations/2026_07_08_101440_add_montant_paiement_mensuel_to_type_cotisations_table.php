<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('type_cotisations', function (Blueprint $table) {
            // Montant du paiement mensuel pour les cotisations personnalisées et AMU (source de vérité pour les prelevements automatiques sur les cotisations liées aux cotisations personnalisées)
            $table->decimal('montant_paiement_mensuel',15,2)->nullable();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('type_cotisations', function (Blueprint $table) {
            $table->dropColumn('montant_paiement_mensuel');
        });
    }
};
