<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seuil_prelevements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->decimal('seuil_pourcentage', 5, 2)->nullable()->comment('Seuil max total règles en % (ex: 40.00)');
            $table->decimal('seuil_montant', 15, 2)->nullable()->comment('Seuil max total règles en montant fixe (FCFA)');
            $table->boolean('est_actif')->default(true);
            $table->text('description')->nullable();
            $table->string('modifie_par')->nullable()->comment('Nom et prénom de l\'admin');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seuil_prelevements');
    }
};
