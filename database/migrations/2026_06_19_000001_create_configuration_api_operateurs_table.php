<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configuration_api_operateurs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('url_api', 500);
            $table->string('url_webhook', 500);
            $table->string('identifiant_api')->nullable();
            $table->text('cle_api')->nullable();           // chiffrée via cast 'encrypted'
            $table->enum('environnement', ['SANDBOX', 'PRODUCTION'])->default('SANDBOX');
            $table->boolean('est_actif')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('configuration_api_operateurs');
    }
};
