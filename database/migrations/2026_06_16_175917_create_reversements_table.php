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
        Schema::create('reversements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('reference');

            $table->decimal('montant_total', 15, 2)->default(0);
            $table->dateTime('date_reversement');
            $table->string('statut'); //EN_ATTENTE|EN_COURS|REVERSE|ANNULE
            $table->string('initie_par'); //Nom et prenom de l'admin qui a initié l'action
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reversements');
    }
};
