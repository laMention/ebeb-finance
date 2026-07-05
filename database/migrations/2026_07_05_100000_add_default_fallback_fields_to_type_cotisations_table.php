<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('type_cotisations', function (Blueprint $table) {
            $table->string('default_type_calcul')->nullable()->comment('FIXE ou POURCENTAGE pour la règle par défaut')->after('description');
            $table->decimal('default_valeur', 15, 2)->nullable()->comment('Valeur du taux ou montant par défaut')->after('default_type_calcul');
            $table->boolean('default_est_actif')->default(false)->comment('Indique si la règle par défaut est activée')->after('default_valeur');
            $table->date('default_date_entree_en_vigueur')->nullable()->comment('Date d\'entrée en vigueur de la règle par défaut')->after('default_est_actif');
        });
    }

    public function down(): void
    {
        Schema::table('type_cotisations', function (Blueprint $table) {
            $table->dropColumn([
                'default_type_calcul',
                'default_valeur',
                'default_est_actif',
                'default_date_entree_en_vigueur',
            ]);
        });
    }
};
