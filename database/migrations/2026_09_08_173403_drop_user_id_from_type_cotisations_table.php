<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Formalise le retrait de `type_cotisations.user_id` : le catalogue de types
 * de cotisations est partagé entre tous les utilisateurs (la relation
 * individuelle est portée uniquement par `regle_prelevements.user_id`),
 * `user_id` sur `type_cotisations` n'a donc plus lieu d'être.
 *
 * Idempotente : sur un environnement où la colonne a déjà été retirée
 * manuellement (hors suivi des migrations), l'index `..._user_id_code_unique`
 * laissé par MySQL (réduit à `UNIQUE(code)` après la suppression de la
 * colonne) est simplement renommé pour refléter sa portée réelle. Sur un
 * environnement où la colonne existe encore, elle est retirée proprement.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('type_cotisations', 'user_id')) {
            try {
                DB::statement('ALTER TABLE type_cotisations DROP INDEX type_cotisations_user_id_code_unique');
            } catch (\Throwable $e) {
                // Index déjà absent sous ce nom — rien à faire.
            }

            Schema::table('type_cotisations', function (Blueprint $table) {
                $table->dropColumn('user_id');
            });

            Schema::table('type_cotisations', function (Blueprint $table) {
                $table->unique('code', 'type_cotisations_code_unique');
            });

            return;
        }

        try {
            DB::statement('ALTER TABLE type_cotisations RENAME INDEX type_cotisations_user_id_code_unique TO type_cotisations_code_unique');
        } catch (\Throwable $e) {
            // Déjà renommé, ou l'index n'existe pas sous ce nom — pas bloquant.
        }
    }

    public function down(): void
    {
        // Non réversible proprement : les anciennes valeurs de `user_id` ne
        // peuvent pas être reconstituées.
    }
};
