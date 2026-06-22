<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('canal');                          // SMS | EMAIL | PUSH | IN_APP
            $table->string('type_notification');              // PAIEMENT_RECU | KYC_VALIDE | …
            $table->string('destinataire');                   // email / téléphone / device_id
            $table->string('sujet')->nullable();
            $table->text('contenu')->nullable();
            $table->string('statut')->default('EN_ATTENTE');  // EN_ATTENTE | ENVOYE | ECHEC
            $table->text('message_erreur')->nullable();
            $table->unsignedTinyInteger('tentatives')->default(0);
            $table->timestamp('envoye_a')->nullable();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};
