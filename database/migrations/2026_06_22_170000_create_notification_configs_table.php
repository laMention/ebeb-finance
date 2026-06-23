<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_configs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('canal')->unique(); // SMS | EMAIL | PUSH | IN_APP
            $table->boolean('est_actif')->default(false);
            $table->string('fournisseur')->nullable();
            $table->text('configuration')->nullable(); // JSON chiffré
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_configs');
    }
};
