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
        Schema::create('declaration_revenus', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->float('montant_revenu')->default(0)->comment('Montant total des revenus déclarés par l\'employeur pour une période donnée');
            $table->decimal('montant_cotisation_regime_base', 15, 2)->default(0)->comment('Montant de la cotisation calculé sur la base du régime de base');
            $table->decimal('montant_cotisation_regime_complementaire', 15,2)->default(0)->comment('Montant de la cotisation calculé sur la base du régime complémentaire');
            $table->decimal('montant_cotisation_mensuelle',15,2)->default(0)->comment('Montant total de la cotisation mensuelle à payer par l\'utilisateur');
            $table->decimal('montant_cotisation_trimestrielle',15,2)->default(0)->comment('Montant total de la cotisation trimestrielle à payer par l\'utilisateur');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('declaration_revenus');
    }
};
