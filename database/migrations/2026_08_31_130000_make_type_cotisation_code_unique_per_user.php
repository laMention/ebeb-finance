<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le code unique global empêchait deux utilisateurs différents de créer
 * chacun une cotisation personnalisée reprenant le même code (ex. « AXA »)
 * — pourtant le service applicatif (TypeCotisationPersonnaliseeService)
 * n'a jamais vérifié que l'unicité par utilisateur. Les types système
 * (user_id NULL, gérés par l'administration) restent protégés au niveau
 * applicatif par la règle `unique:type_cotisations,code` de leurs Form
 * Requests, indépendante de cet index.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('type_cotisations', function (Blueprint $table) {
            $table->dropUnique('type_cotisations_code_unique');
            $table->unique(['user_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::table('type_cotisations', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'code']);
            $table->unique('code');
        });
    }
};
