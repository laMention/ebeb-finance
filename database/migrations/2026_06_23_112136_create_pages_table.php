<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('titre');
            $table->longText('contenu')->nullable();
            $table->string('slug')->unique();
            $table->enum('statut', ['BROUILLON', 'PUBLIE'])->default('BROUILLON');
            $table->string('type_page')->default('PERSONNALISEE');
            $table->string('meta_titre')->nullable();
            $table->text('meta_description')->nullable();
            $table->unsignedSmallInteger('ordre')->default(0);
            $table->timestamp('publie_le')->nullable();
            $table->foreignUuid('cree_par')->nullable()->constrained('administrateurs')->nullOnDelete();
            $table->foreignUuid('modifie_par')->nullable()->constrained('administrateurs')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
