<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Agrandir contenu : VARCHAR(255) insuffisant pour du JSON long
        DB::statement("ALTER TABLE notifications MODIFY COLUMN contenu TEXT");

        Schema::table('notifications', function (Blueprint $table) {
            $table->string('titre')->nullable()->after('type');
            $table->boolean('est_lu')->default(false)->after('est_envoye');
            $table->timestamp('lu_le')->nullable()->after('est_lu');
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn(['titre', 'est_lu', 'lu_le']);
        });

        DB::statement("ALTER TABLE notifications MODIFY COLUMN contenu VARCHAR(255)");
    }
};
