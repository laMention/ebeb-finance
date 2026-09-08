<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partenaire_comptes_destination', function (Blueprint $table) {
            $table->boolean('est_principal')->default(false)->after('est_actif');
        });
    }

    public function down(): void
    {
        Schema::table('partenaire_comptes_destination', function (Blueprint $table) {
            $table->dropColumn('est_principal');
        });
    }
};
