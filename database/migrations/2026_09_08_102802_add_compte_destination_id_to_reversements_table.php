<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reversements', function (Blueprint $table) {
            $table->foreignUuid('compte_destination_id')->nullable()
                ->after('partenaires_financier_id')
                ->constrained('partenaire_comptes_destination')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('reversements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('compte_destination_id');
        });
    }
};
