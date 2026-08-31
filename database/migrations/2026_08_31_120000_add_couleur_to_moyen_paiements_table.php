<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('moyen_paiements', function (Blueprint $table) {
            $table->string('couleur', 7)->nullable()->comment('Couleur associée au moyen de paiement, format hexadécimal (#RRGGBB)')->after('logo');
        });
    }

    public function down(): void
    {
        Schema::table('moyen_paiements', function (Blueprint $table) {
            $table->dropColumn('couleur');
        });
    }
};
