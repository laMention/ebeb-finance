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
        Schema::table('reversements', function (Blueprint $table) {
            //
            $table->foreignUuid('partenaire_id')->nullable()->references('id')->on('partenaires_financiers')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reversements', function (Blueprint $table) {
            //
            $table->dropColumn(['partenaire_id']);
        });
    }
};
