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
        Schema::create('alertes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type_alerte')->nullable(); 
            $table->string('titre')->nullable(); 
            $table->text('description')->nullable(); 
            $table->enum('niveau', ['INFO', 'AVERTISSEMENT', 'CRITIQUE', 'SUCCES'])->default('INFO');
            $table->boolean('est_lu')->default(false); 
            $table->dateTime('date_lecture')->nullable(); 
            $table->string('lien_vers_action_concerne')->nullable();
            $table->softDeletes(); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alertes');
    }
};
