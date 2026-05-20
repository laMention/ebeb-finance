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
        Schema::create('paiement_entrants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreignUuid('compte_mobile_money_id')->references('id')->on('compte_mobile_moneys')->onDelete('cascade')->comment('Compte mobile money utilisé pour ce paiement');
            $table->decimal('montant_brut', 15, 2);
            $table->enum('statut', ['EN_ATTENTE', 'SUCCES', 'ECHEC','PARTIELLEMENT_TRAITE']);
            $table->string('reference_externe')->unique()->comment('Référence de la transaction provenant du fournisseur de paiement'); // Référence de la transaction provenant du fournisseur de paiement
            $table->string('operateur_source');
            $table->string('qr_code_ref')->nullable()->comment('Référence pour le QR code, si applicable'); // Référence pour le QR code, si applicable
            $table->string('description')->nullable()->comment('Description de l\'opération du paiement effectué par le client');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paiement_entrants');
    }
};
