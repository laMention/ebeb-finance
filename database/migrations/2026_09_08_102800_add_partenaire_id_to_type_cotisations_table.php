<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('type_cotisations', function (Blueprint $table) {
            $table->foreignUuid('partenaire_id')->nullable()
                ->after('user_id')
                ->constrained('partenaires_financiers')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('type_cotisations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('partenaire_id');
        });
    }
};
