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
            $table->enum('transmission_statut', ['NON_TRANSMIS', 'TRANSMIS', 'ECHEC'])
                ->default('NON_TRANSMIS')->after('statut');
            $table->jsonb('transmission_reponse')->nullable()->after('transmission_statut');
            $table->timestamp('transmission_date')->nullable()->after('transmission_reponse');
            $table->jsonb('donnees_transmises')->nullable()->after('transmission_date');
            $table->string('donnees_checksum')->nullable()->after('donnees_transmises');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reversements', function (Blueprint $table) {
            $table->dropColumn([
                'transmission_statut', 'transmission_reponse', 'transmission_date',
                'donnees_transmises', 'donnees_checksum',
            ]);
        });
    }
};
