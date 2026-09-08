<?php

use App\Models\PartenairesFinancier;
use App\Models\TypeCotisation;
use Illuminate\Database\Migrations\Migration;

/**
 * Migration de données : rattache le type de cotisation CNPS au partenaire
 * CNPS existant (pas de changement de schéma, `partenaire_id` existe déjà
 * sur `type_cotisations`). AMU n'est pas traité ici : aucun partenaire AMU
 * n'existe encore en base.
 */
return new class extends Migration
{
    public function up(): void
    {
        $partenaireId = PartenairesFinancier::where('type', 'CNPS')->value('id');

        if (!$partenaireId) {
            return;
        }

        TypeCotisation::where('code', 'CNPS')->update(['partenaire_id' => $partenaireId]);
    }

    public function down(): void
    {
        TypeCotisation::where('code', 'CNPS')->update(['partenaire_id' => null]);
    }
};
