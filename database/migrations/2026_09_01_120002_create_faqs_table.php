<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faqs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->text('question');
            $table->text('reponse');
            $table->string('categorie')->nullable();
            $table->enum('statut', ['BROUILLON', 'PUBLIE'])->default('BROUILLON');
            $table->unsignedInteger('ordre')->default(0);
            $table->foreignUuid('cree_par')->nullable()->constrained('administrateurs')->onDelete('set null');
            $table->foreignUuid('modifie_par')->nullable()->constrained('administrateurs')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faqs');
    }
};
