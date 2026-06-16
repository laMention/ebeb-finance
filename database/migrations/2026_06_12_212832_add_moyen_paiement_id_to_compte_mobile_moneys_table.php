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
        Schema::table('compte_mobile_moneys', function (Blueprint $table) {
            //
            $table->foreignUuid('moyen_paiement_id')->nullable()->references('id')->on('moyen_paiements')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('compte_mobile_moneys', function (Blueprint $table) {
            //
            $table->dropColumn(['moyen_paiement_id']);
        });
    }
};
