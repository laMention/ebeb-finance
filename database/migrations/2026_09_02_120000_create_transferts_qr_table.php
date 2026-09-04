<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Paiements entre comptes mobile money ebeb initiés par scan de QR code
     * — trace de chaque tentative (REUSSI/ECHEC), en plus du `PaiementEntrant`
     * que `PaiementService::traiterPaiement()` crée pour la répartition
     * elle-même. Aucune API opérateur (MTN/Wave/Orange/Moov) réelle
     * intégrée pour l'instant : rien ne débite le compte de l'expéditeur.
     */
    public function up(): void
    {
        Schema::create('transferts_qr', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('expediteur_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('destinataire_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('compte_mobile_money_expediteur_id')
                ->constrained('compte_mobile_moneys')->cascadeOnDelete();
            $table->foreignUuid('compte_mobile_money_destinataire_id')
                ->constrained('compte_mobile_moneys')->cascadeOnDelete();
            $table->string('operateur');
            $table->decimal('montant', 14, 2);
            $table->string('reference')->unique();
            $table->string('statut')->default('SIMULE');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transferts_qr');
    }
};
