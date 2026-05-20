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
        Schema::create('log_audits', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('administrateur_id')->nullable()->constrained('administrateurs')->onDelete('set null');
            $table->string('action');
            $table->string('entite_cible')->nullable();
            $table->uuid('entite_id')->nullable();
            $table->jsonb('donnees_avant')->nullable();
            $table->jsonb('donnees_apres')->nullable();
            $table->string('ip_adresse')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('log_audits');
    }
};
