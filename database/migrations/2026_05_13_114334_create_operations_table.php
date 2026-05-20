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
        Schema::create('operations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreignUuid('type_cotisation_id')->nullable()->references('id')->on('type_cotisations')->onDelete('cascade');
            $table->foreignUuid('objectif_epargne_id')->nullable()->references('id')->on('objectif_epargnes')->onDelete('cascade');
            $table->foreignUuid('paiement_entrant_id')->nullable()->references('id')->on('paiement_entrants')->onDelete('cascade');
            $table->enum('type_operation', ['PRELEVEMENT_COTISATION', 'PRELEVEMENT_EPARGNE', 'RETRAIT_EPARGNE','REVERSEMENT','ESCROW','REVERSEMENT_ESCROW','COMMISSION','RETRAIT_COTISATION','VIREMENT'])->comment('Type de l\'opération');
            $table->decimal('montant', 15, 2)->default(0);
            $table->text('description')->nullable();
            $table->string('libelle')->nullable();
            $table->enum('statut', ['EN_ATTENTE', 'SUCCES', 'ECHEC'])->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('operations');
    }
};
