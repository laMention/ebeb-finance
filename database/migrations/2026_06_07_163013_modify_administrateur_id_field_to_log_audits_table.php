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
        Schema::table('log_audits', function (Blueprint $table) {
            //
             $table->renameColumn('administrateur_id', 'utilisateur');
        });

        Schema::table('log_audits', function (Blueprint $table) {
            $table->string('utilisateur')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('log_audits', function (Blueprint $table) {
            $table->renameColumn('utilisateur', 'administrateur_id');
        });

        Schema::table('log_audits', function (Blueprint $table) {
            $table->unsignedBigInteger('administrateur_id')->nullable()->change();
        });
    }
};
