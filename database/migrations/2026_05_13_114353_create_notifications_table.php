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
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->string('canal'); // "EMAIL", "SMS", "PUSH"
            $table->string('type')->comment('TRANSACTION|RAPPEL_COTISATION|KYC_STATUT|ALERTE'); // "TRANSACTION|RAPPEL_COTISATION|KYC_STATUT|ALERTE"
            $table->string('contenu');
            $table->boolean('est_envoye')->default(false);
            $table->timestamp('envoye_le')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
