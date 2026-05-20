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
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nom');
            $table->string('prenom');
            // Si email existe, il doit être unique et non nullable, sinon il peut être nullable
            $table->date('date_naissance')->nullable();
            $table->string('lieu_naissance')->nullable();
            $table->string('telephone')->unique();
            $table->string('profession')->nullable();
            $table->string('numero_cnps')->nullable();
            $table->string('numero_cmu')->nullable();
            $table->enum('statut', ['EN_ATTENTE', 'ACTIF', 'SUSPENDU', 'REJETE'])->default('EN_ATTENTE'); // EN_ATTENTE, VALIDE, REJETE
            $table->string('email')->nullable();
            $table->timestamp('email_verified_at')->nullable();

            $table->enum('type_carte', ['BASIC', 'VIP'])->nullable();
            $table->string('pays')->nullable();
            $table->string('ville')->nullable();
            $table->string('quartier')->nullable();
            $table->string('village')->nullable();
            $table->string('adresse_postale')->nullable();
            $table->enum('sexe', ['HOMME', 'FEMME'])->nullable();
            $table->enum('situation_familiale', ['CELIBATAIRE', 'MARIE', 'DIVORCE', 'VEUF'])->nullable();
            $table->integer('nombre_enfants')->default(0);
            $table->datetime('date_activation')->nullable();

            $table->string('photo_profil')->nullable();

            $table->string('password'); //Code PIN hashé
            $table->rememberToken();
            $table->datetime('derniere_connexion')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            // $table->uuid('user_id')->nullable()->index(); // Clé étrangère vers la table users
            $table->foreignUuid('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
