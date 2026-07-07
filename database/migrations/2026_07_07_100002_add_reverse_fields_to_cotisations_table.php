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
        Schema::table('cotisations', function (Blueprint $table) {
            $table->boolean('reverse')->default(false)->after('statut');
            $table->timestamp('reverse_le')->nullable()->after('reverse');
            $table->foreignUuid('reversement_id')->nullable()->after('reverse_le')
                ->constrained('reversements')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cotisations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reversement_id');
            $table->dropColumn(['reverse', 'reverse_le']);
        });
    }
};
