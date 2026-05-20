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
        Schema::create('parametre_globals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('cle');
            $table->string('valeur');
            $table->string('description')->nullable();
            $table->foreignUuid('modifie_par')->nullable()->constrained('administrateurs')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parametre_globals');
    }
};
