<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solde_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreignUuid('type_cotisation_id')
                ->nullable()
                ->references('id')
                ->on('type_cotisations')
                ->onDelete('set null');
            $table->string('type_categorie', 50)->comment('EPARGNE | COTISATION_CNPS | COTISATION_AMU | COTISATION_PERSONNALISEE | ASSURANCE_PERSONNALISEE');
            $table->string('libelle');
            $table->decimal('solde', 15, 2)->default(0);
            $table->decimal('total_verse', 15, 2)->default(0);
            $table->decimal('total_reporte', 15, 2)->default(0)->comment('Report vers la prochaine période annuelle');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['user_id', 'type_categorie', 'type_cotisation_id'], 'solde_categories_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solde_categories');
    }
};
