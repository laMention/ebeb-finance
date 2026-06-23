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
        Schema::table('configuration_api_operateurs', function (Blueprint $table) {
            //
            $table->foreignUuid('moyen_paiement_id')->constrained('moyen_paiements');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('configuration_api_operateurs', function (Blueprint $table) {
            //
            $table->dropColumn('moyen_paiement_id');

        });
    }
};
