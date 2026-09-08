<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Index unique fonctionnel : une seule règle active par (user_id,
     * type_cotisation_id). L'expression vaut NULL pour les lignes
     * soft-deleted, que MySQL exclut de la vérification d'unicité — les
     * lignes supprimées ne bloquent donc jamais un futur ré-ajout du même
     * type.
     */
    public function up(): void
    {
        DB::statement(
            'ALTER TABLE regle_prelevements
                ADD UNIQUE INDEX regle_prelevements_user_type_active_unique (
                    (CASE WHEN deleted_at IS NULL THEN CONCAT(user_id, \'|\', type_cotisation_id) END)
                )'
        );
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE regle_prelevements DROP INDEX regle_prelevements_user_type_active_unique');
    }
};
