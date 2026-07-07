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
        Schema::table('partenaires_financiers', function (Blueprint $table) {
            $table->boolean('est_actif')->default(true)->after('type');
            $table->string('url_api_reversement')->nullable()->after('est_actif');
            $table->string('url_api_consultation')->nullable()->after('url_api_reversement');
            $table->string('url_webhook')->nullable()->after('url_api_consultation');
            $table->enum('methode_authentification', ['API_KEY', 'OAUTH', 'BASIC_AUTH'])->nullable()->after('url_webhook');
            $table->text('identifiants_api')->nullable()->after('methode_authentification');
            $table->enum('format_echange', ['JSON', 'XML'])->default('JSON')->after('identifiants_api');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('partenaires_financiers', function (Blueprint $table) {
            $table->dropColumn([
                'est_actif', 'url_api_reversement', 'url_api_consultation', 'url_webhook',
                'methode_authentification', 'identifiants_api', 'format_echange',
            ]);
        });
    }
};
