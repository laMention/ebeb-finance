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
        Schema::create('moyen_paiements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('libelle');
            $table->string('code');
            $table->string('logo')->nullable();
            $table->string('operateur')->nullable();
            $table->boolean('par_defaut')->default(false);
            $table->boolean('est_actif')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('moyen_paiements');
    }
};
