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
        Schema::create('session_otps', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // $table->uuid('user_id')->index(); // Clé étrangère vers la table users
            $table->foreignUuid('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->string('code_otp');
            $table->string('contexte'); // INSCRIPTION, CONNEXION, RESET_PIN
            $table->boolean('est_utilise')->default(false);
            $table->timestamp('expire_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('session_otps');
    }
};
