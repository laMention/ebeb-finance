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
        Schema::create('administrateurs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nom');
            $table->string('prenom')->nullable();
            $table->string('ville')->nullable();
            $table->string('adresse')->nullable();
            $table->string('email')->unique();
            $table->string('telephone')->unique();
            $table->string('password');
            $table->string('photo_profil')->nullable();
            $table->enum('statut_compte', ['ACTIF', 'INACTIF'])->default('INACTIF');
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('administrateur_password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('administrateurs');
    }
};
