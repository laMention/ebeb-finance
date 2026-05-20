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
        Schema::create('compte_mobile_money', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // $table->uuid('user_id')->index(); // Clé étrangère vers la table users
            $table->foreignUuid('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->enum('operateur', ['WAVE', 'ORANGE', 'MTN', 'MOOV']);
            $table->string('numero_compte');
            $table->boolean('est_principal');
            $table->boolean('est_actif');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compte_mobile_money');
    }
};
