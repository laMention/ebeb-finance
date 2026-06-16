<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('portefeuille_epargnes', function (Blueprint $table) {
            $table->decimal('solde_epargne_disponible', 15, 2)->default(0)->after('total_epargne');
            $table->decimal('montant_net_total', 15, 2)->default(0)->after('total_recu_brut');
        });
    }

    public function down(): void
    {
        Schema::table('portefeuille_epargnes', function (Blueprint $table) {
            $table->dropColumn(['solde_epargne_disponible', 'montant_net_total']);
        });
    }
};
