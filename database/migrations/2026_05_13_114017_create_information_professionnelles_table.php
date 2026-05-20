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
        Schema::create('information_professionnelles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // $table->uuid('user_id')->index(); // Clé étrangère vers la table users
            $table->foreignUuid('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->string('categorie_professionnelle')->nullable();
            $table->string('metier')->nullable();
            $table->decimal('revenu_mensuel', 10, 2)->nullable();
            $table->date('date_debut_activite')->nullable();
            $table->date('date_fin_activite')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('information_professionnelles');
    }
};
