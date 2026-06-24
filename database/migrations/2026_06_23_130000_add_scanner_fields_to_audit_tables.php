<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_securite_vulnerabilites', function (Blueprint $table) {
            $table->boolean('correctable_auto')->default(false)->after('notes_correction');
            $table->string('correction_action')->nullable()->after('correctable_auto');
            $table->string('correction_label')->nullable()->after('correction_action');
        });
        Schema::table('audit_securite_rapports', function (Blueprint $table) {
            $table->boolean('est_en_cours')->default(false)->after('statut');
            $table->unsignedInteger('nb_correctables')->default(0)->after('nb_corrige');
        });
    }

    public function down(): void
    {
        Schema::table('audit_securite_vulnerabilites', function (Blueprint $table) {
            $table->dropColumn(['correctable_auto', 'correction_action', 'correction_label']);
        });
        Schema::table('audit_securite_rapports', function (Blueprint $table) {
            $table->dropColumn(['est_en_cours', 'nb_correctables']);
        });
    }
};