<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('document_k_y_c_s', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // $table->uuid('user_id')->index(); // Clé étrangère vers la table users
            $table->foreignUuid('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->enum('type_document', ['CNI', 'PASSPORT', 'PERMIS_CONDUIRE','ATTESTATION']);
            $table->string('numero_document')->nullable();
            $table->date('document_etablie_le')->nullable();
            $table->date('document_expire_le')->nullable();
            $table->string('url_recto')->nullable();
            $table->string('url_verso')->nullable();
            $table->string('url_selfie')->nullable();
            $table->enum('statut', ['EN_ATTENTE', 'VALIDE', 'REJETE'])->default('EN_ATTENTE'); // EN_ATTENTE, VALIDE, REJETE
            $table->string('motif_rejet')->nullable();
            $table->uuid('valide_par')->nullable(); // Clé étrangère vers l'administrateur qui a validé
            $table->timestamp('validated_at')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_k_y_c_s');
    }
};
