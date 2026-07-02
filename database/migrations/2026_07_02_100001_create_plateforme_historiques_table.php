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
        Schema::create('plateforme_historiques', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('statut_precedent', ['ACTIVE', 'MAINTENANCE', 'DESACTIVEE']);
            $table->enum('statut_nouveau', ['ACTIVE', 'MAINTENANCE', 'DESACTIVEE']);
            $table->text('message')->nullable();
            $table->text('motif')->nullable();
            $table->timestamp('date_debut')->nullable();
            $table->timestamp('date_fin')->nullable();
            $table->foreignUuid('modifie_par')->nullable()->constrained('administrateurs')->onDelete('set null');
            $table->string('ip_adresse')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plateforme_historiques');
    }
};
