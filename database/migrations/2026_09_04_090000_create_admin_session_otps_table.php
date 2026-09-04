<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Deuxième facteur de connexion admin (2FA). Table dédiée — distincte de
     * `session_otps` (couplée en dur à `users` pour le mobile) — afin de ne
     * jamais toucher le parcours OTP mobile existant. `canal` anticipe une
     * évolution future (ex. TOTP/Google Authenticator) sans migration
     * supplémentaire.
     */
    public function up(): void
    {
        Schema::create('admin_session_otps', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('administrateur_id')
                ->references('id')->on('administrateurs')->onDelete('cascade');
            $table->string('code_otp');
            $table->string('canal')->default('EMAIL');
            $table->boolean('est_utilise')->default(false);
            $table->unsignedTinyInteger('tentatives')->default(0);
            $table->timestamp('expire_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_session_otps');
    }
};
