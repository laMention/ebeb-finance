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
        Schema::create('escrows', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreignUuid('operation_id')->references('id')->on('operations')->onDelete('cascade');
            $table->decimal('montant', 15, 2)->default(0);
            $table->enum('statut', ['EN_ATTENTE', 'SUCCES', 'ECHEC'])->nullable();
            $table->string('raison_blocage')->nullable();
            $table->timestamp('libere_at')->nullable();
            $table->timestamps();

            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('escrows');
    }
};
