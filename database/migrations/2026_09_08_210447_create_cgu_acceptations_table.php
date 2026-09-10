<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Journal d'acceptation des CGU, append-only : une ligne par acceptation,
 * jamais écrasée ni supprimée. `user_id` est nullable car l'acceptation a
 * lieu avant la création du compte (modale CGU au tap « S'inscrire ») — elle
 * est rattachée au `user_id` dans la même transaction que la création du
 * compte (`InscriptionService::inscrire()`).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cgu_acceptations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('cgu_version_id')->constrained('cgu_versions');
            // nullOnDelete (pas cascade) : une preuve de consentement doit
            // survivre à la suppression du compte auquel elle est rattachée.
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('accepte_le');
            $table->string('ip_adresse')->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cgu_acceptations');
    }
};
