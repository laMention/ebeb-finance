<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plateforme_surface_etats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('surface', ['SITE_WEB', 'PANEL_ADMIN'])->unique();
            $table->enum('statut', ['ACTIF', 'DESACTIVE'])->default('ACTIF');
            $table->text('message')->nullable();
            $table->foreignUuid('modifie_par')->nullable()->constrained('administrateurs')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plateforme_surface_etats');
    }
};
