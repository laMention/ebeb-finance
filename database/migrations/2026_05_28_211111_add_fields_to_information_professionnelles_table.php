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
        Schema::table('information_professionnelles', function (Blueprint $table) {
            $table->string('ville')->nullable();
            $table->string('quartier')->nullable();
            $table->string('commune_sous_prefecture')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('information_professionnelles', function (Blueprint $table) {
            $table->dropColumn(['ville','quartier','commune_sous_prefecture']);
        });
    }
};
