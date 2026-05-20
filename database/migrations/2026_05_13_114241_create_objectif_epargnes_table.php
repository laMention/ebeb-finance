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
        Schema::create('objectif_epargnes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // $table->uuid('user_id');
            $table->foreignUuid('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->string('libelle');
            $table->decimal('montant_cible', 15, 2);
            $table->decimal('montant_epargne', 15, 2);
            $table->date('date_limite');
            $table->boolean('est_actif')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('objectif_epargnes');
    }
};
