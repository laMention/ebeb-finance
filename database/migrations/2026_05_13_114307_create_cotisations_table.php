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
        Schema::create('cotisations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreignUuid('type_cotisation_id')->references('id')->on('type_cotisations')->onDelete('cascade');
            $table->integer('mois');
            $table->integer('annee');
            $table->decimal('montant_verse', 15, 2);
            $table->decimal('montant_objectif', 15, 2);
            $table->enum('statut', ['A_JOUR', 'EN_RETARD', 'PARTIEL']);
            $table->string('numero_adherent');
            $table->timestamp('date_paiement')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cotisations');
    }
};
