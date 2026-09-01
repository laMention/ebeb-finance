<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('reference')->unique();
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->string('objet');
            $table->text('description');
            $table->enum('severite', ['FAIBLE', 'NORMALE', 'HAUTE', 'CRITIQUE'])->default('NORMALE');
            $table->enum('statut', ['OUVERT', 'EN_COURS', 'RESOLU', 'FERME'])->default('OUVERT');
            // Origine de la réclamation — MOBILE aujourd'hui (formulaire « Signaler un
            // problème »), distingue les futures créations depuis d'autres canaux.
            $table->string('source')->default('MOBILE');
            $table->timestamp('resolu_le')->nullable();
            $table->foreignUuid('traite_par')->nullable()->constrained('administrateurs')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
