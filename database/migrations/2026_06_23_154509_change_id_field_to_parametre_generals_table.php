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
        Schema::table('parametre_generals', function (Blueprint $table) {
            $table->dropColumn('id');
        });

        Schema::table('parametre_generals', function (Blueprint $table) {
            $table->uuid('id')->primary();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        
        Schema::table('parametre_generals', function (Blueprint $table) {
            $table->unsignedBigInteger('id');
        });
    }
};
