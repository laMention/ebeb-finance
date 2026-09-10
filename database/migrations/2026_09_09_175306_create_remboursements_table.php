<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Journal des remboursements de prélèvements erronés (panel admin). Un seul
 * remboursement possible par opération d'origine (UNIQUE sur `operation_id`)
 * — filet de sécurité en base contre un double remboursement, en plus de la
 * vérification applicative. Registre financier immuable : pas de soft
 * delete, pas de route de suppression.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('remboursements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('operation_id')->unique()->constrained('operations');
            $table->foreignUuid('operation_remboursement_id')->constrained('operations');
            $table->foreignUuid('user_id')->constrained('users');
            $table->foreignUuid('type_cotisation_id')->nullable()->constrained('type_cotisations')->nullOnDelete();
            $table->foreignUuid('administrateur_id')->constrained('administrateurs');
            $table->decimal('montant_preleve', 15, 2);
            $table->decimal('montant_rembourse', 15, 2);
            $table->text('motif');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('remboursements');
    }
};
