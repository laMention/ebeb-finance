<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_securite_rapports', function (Blueprint $table) {
            $table->id();
            $table->string('titre')->default('Audit de securite');
            $table->string('version')->default('1.0');
            $table->enum('statut', ['EN_COURS', 'TERMINE', 'ARCHIVE'])->default('TERMINE');
            $table->unsignedInteger('nb_critique')->default(0);
            $table->unsignedInteger('nb_eleve')->default(0);
            $table->unsignedInteger('nb_moyen')->default(0);
            $table->unsignedInteger('nb_faible')->default(0);
            $table->unsignedInteger('nb_info')->default(0);
            $table->unsignedInteger('nb_corrige')->default(0);
            $table->text('notes')->nullable();
            $table->char('realise_par', 36)->nullable();
            $table->foreign('realise_par')->references('id')->on('administrateurs')->nullOnDelete();
            $table->timestamp('date_audit')->useCurrent();
            $table->timestamps();
        });

        Schema::create('audit_securite_vulnerabilites', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rapport_id');
            $table->foreign('rapport_id')->references('id')->on('audit_securite_rapports')->cascadeOnDelete();
            $table->string('code')->unique();
            $table->string('titre');
            $table->enum('criticite', ['CRITIQUE', 'ELEVE', 'MOYEN', 'FAIBLE', 'INFO']);
            $table->enum('statut', ['DETECTE', 'EN_COURS', 'CORRIGE', 'ACCEPTE', 'FAUX_POSITIF'])->default('DETECTE');
            $table->string('categorie');
            $table->text('description');
            $table->text('impact');
            $table->text('recommandation');
            $table->string('fichier')->nullable();
            $table->string('ligne')->nullable();
            $table->timestamp('date_detection')->useCurrent();
            $table->timestamp('date_correction')->nullable();
            $table->char('corrige_par', 36)->nullable();
            $table->foreign('corrige_par')->references('id')->on('administrateurs')->nullOnDelete();
            $table->text('notes_correction')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_securite_vulnerabilites');
        Schema::dropIfExists('audit_securite_rapports');
    }
};