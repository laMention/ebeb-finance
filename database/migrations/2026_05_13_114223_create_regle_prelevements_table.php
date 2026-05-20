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
        Schema::create('regle_prelevements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // $table->uuid('user_id');
            $table->foreignUuid('user_id')->references('id')->on('users')->onDelete('cascade');
            // $table->uuid('type_cotisation_id');
            $table->foreignUuid('type_cotisation_id')->references('id')->on('type_cotisations')->onDelete('cascade');
            $table->enum('type_calcul', ['FIXE', 'POURCENTAGE']);
            $table->decimal('valeur', 15, 2);
            $table->boolean('est_actif')->default(true);
            $table->integer('ordre_priorite')->default(0);            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('regle_prelevements');
    }
};
