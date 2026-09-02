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
        Schema::create('partenaire_comptes_destination', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('partenaires_financier_id')->constrained('partenaires_financiers')->onDelete('cascade');
            $table->string('libelle');
            $table->enum('type_compte', ['BANQUE', 'MOBILE_MONEY', 'AUTRE']);
            $table->string('numero_compte');
            $table->string('banque_operateur')->nullable();
            $table->boolean('est_actif')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partenaire_comptes_destination');
    }
};
