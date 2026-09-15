<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jetons FCM par appareil — un utilisateur peut avoir plusieurs appareils
 * (comme `comptes_mobile_moneys`). Pas de soft delete : un jeton
 * invalide/désinstallé est simplement supprimé (voir
 * NotificationService::envoyerPush()).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_tokens', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('token')->unique();
            $table->enum('plateforme', ['ANDROID', 'IOS']);
            $table->timestamp('derniere_utilisation_le')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_tokens');
    }
};
