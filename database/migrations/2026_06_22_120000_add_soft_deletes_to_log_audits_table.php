<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('log_audits', function (Blueprint $table) {
            $table->softDeletes()->after('ip_adresse');
        });
    }

    public function down(): void
    {
        Schema::table('log_audits', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
