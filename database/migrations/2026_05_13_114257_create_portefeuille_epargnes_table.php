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
        Schema::create('portefeuille_epargnes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // $table->uuid('user_id');
            $table->foreignUuid('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->decimal('total_epargne', 15, 2)->default(0);
            $table->decimal('total_verse_cotisations', 15, 2)->default(0);
            $table->decimal('total_commissions_payees', 15, 2)->default(0);
            $table->decimal('total_recu_brut', 15, 2)->default(0);
            $table->integer('mois_reference');
            $table->integer('annee_reference');
            $table->timestamp('recalcule_at')->nullable();
            $table->timestamps();
            $table->softDeletes(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('portefeuille_epargnes');
    }
};
