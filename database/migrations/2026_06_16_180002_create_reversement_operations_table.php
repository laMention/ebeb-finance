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
        Schema::create('reversement_operations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('reversement_id')->nullable()->references('id')->on('reversements')->onDelete('cascade');
            $table->foreignUuid('operation_id')->nullable()->references('id')->on('operations')->onDelete('cascade');
            $table->decimal('montant',15,2)->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reversement_operations');
    }
};
