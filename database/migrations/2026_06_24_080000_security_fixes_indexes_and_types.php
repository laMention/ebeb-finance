<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // SEC-004 : montant_revenu float → decimal(15,2)
        Schema::table('declaration_revenus', function (Blueprint $table) {
            $table->decimal('montant_revenu', 15, 2)->default(0)->change();
        });

        // SEC-010 + SEC-016 : contrainte UNIQUE sur reference_externe (empêche les doublons en concurrence)
        Schema::table('paiement_entrants', function (Blueprint $table) {
            $table->unique('reference_externe', 'paiement_entrants_reference_unique');
        });

        // SEC-016 : index composite sur cotisations pour les lookups financiers
        Schema::table('cotisations', function (Blueprint $table) {
            $table->index(['user_id', 'type_cotisation_id', 'annee'], 'cotisations_user_type_annee_idx');
        });

        // SEC-016 + SEC-021 : contrainte UNIQUE composite sur portefeuille_epargnes (empêche les doublons mensuels)
        Schema::table('portefeuille_epargnes', function (Blueprint $table) {
            $table->unique(['user_id', 'mois_reference', 'annee_reference'], 'portefeuille_user_mois_annee_unique');
        });

        // SEC-016 : index sur operations pour les rapports financiers
        Schema::table('operations', function (Blueprint $table) {
            $table->index(['user_id', 'type_operation', 'statut'], 'operations_user_type_statut_idx');
        });
    }

    public function down(): void
    {
        Schema::table('declaration_revenus', function (Blueprint $table) {
            $table->float('montant_revenu')->default(0)->change();
        });

        Schema::table('paiement_entrants', function (Blueprint $table) {
            $table->dropUnique('paiement_entrants_reference_unique');
        });

        Schema::table('cotisations', function (Blueprint $table) {
            $table->dropIndex('cotisations_user_type_annee_idx');
        });

        Schema::table('portefeuille_epargnes', function (Blueprint $table) {
            $table->dropUnique('portefeuille_user_mois_annee_unique');
        });

        Schema::table('operations', function (Blueprint $table) {
            $table->dropIndex('operations_user_type_statut_idx');
        });
    }
};
