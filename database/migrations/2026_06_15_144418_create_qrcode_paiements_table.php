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
        Schema::create('qrcode_paiements', function (Blueprint $table) {
            $table->uuid()->primary();
            $table->foreignUuid('user_id')->nullable()->references('id')->on('users')->onDelete('cascade');
            $table->foreignUuid('compte_mobile_money_id')->nullable()->references('id')->on('compte_mobile_moneys')->onDelete('cascade');
            $table->string('reference');
            $table->string('valeur')->nullable();
            $table->boolean('est_actif')->defaut(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qrcode_paiements');
    }
};
