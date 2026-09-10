<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Snapshot immuable du contenu des CGU à chaque publication. Découplé de
 * `pages.contenu` (qui est mutable en place) pour garder une preuve figée du
 * texte exact accepté par un utilisateur, même si la page CMS est modifiée
 * plus tard.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cgu_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('page_id')->nullable()->constrained('pages')->nullOnDelete();
            $table->unsignedInteger('numero_version');
            $table->string('titre');
            $table->longText('contenu');
            $table->boolean('est_active')->default(false);
            $table->timestamp('publie_le')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cgu_versions');
    }
};
