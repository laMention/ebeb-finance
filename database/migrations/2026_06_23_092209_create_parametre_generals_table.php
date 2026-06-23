<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parametre_generals', function (Blueprint $table) {
            $table->id();

            // Informations générales
            $table->string('nom_plateforme')->default('Ebeb Finance');
            $table->string('slogan')->nullable();
            $table->text('description_courte')->nullable();
            $table->string('site_web')->nullable();
            $table->string('email_contact')->nullable();
            $table->string('telephone_contact')->nullable();
            $table->string('copyright')->nullable();

            // Identité visuelle (chemins fichiers dans storage/app/public)
            $table->string('logo_principal')->nullable();
            $table->string('logo_favicon')->nullable();
            $table->string('logo_email')->nullable();
            $table->string('icone_application')->nullable();

            // Réseaux sociaux
            $table->string('facebook')->nullable();
            $table->string('instagram')->nullable();
            $table->string('linkedin')->nullable();
            $table->string('x_twitter')->nullable();
            $table->string('tiktok')->nullable();
            $table->string('youtube')->nullable();
            $table->string('whatsapp')->nullable();

            // Configuration SEO
            $table->string('seo_titre')->nullable();
            $table->text('seo_description')->nullable();
            $table->text('seo_mots_cles')->nullable();
            $table->string('seo_image_og')->nullable();
            $table->string('seo_url_canonique')->nullable();

            // Audit
            $table->string('modifie_par')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parametre_generals');
    }
};
