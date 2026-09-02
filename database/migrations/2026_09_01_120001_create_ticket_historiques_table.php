<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Traçabilité des changements de statut/sévérité d'un ticket — même schéma
 * que `plateforme_historiques`, seule table d'historique dédiée existante
 * dans le projet (voir PlateformeHistorique / PlateformeStateService).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_historiques', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('ticket_id')->constrained('tickets')->onDelete('cascade');
            $table->enum('statut_precedent', ['OUVERT', 'EN_COURS', 'RESOLU', 'FERME'])->nullable();
            $table->enum('statut_nouveau', ['OUVERT', 'EN_COURS', 'RESOLU', 'FERME'])->nullable();
            $table->enum('severite_precedente', ['FAIBLE', 'NORMALE', 'HAUTE', 'CRITIQUE'])->nullable();
            $table->enum('severite_nouvelle', ['FAIBLE', 'NORMALE', 'HAUTE', 'CRITIQUE'])->nullable();
            $table->text('commentaire')->nullable();
            $table->foreignUuid('modifie_par')->nullable()->constrained('administrateurs')->onDelete('set null');
            $table->string('ip_adresse')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_historiques');
    }
};
