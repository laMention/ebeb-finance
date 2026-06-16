<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Convert enum to string for extensibility (spec requirement)
        DB::statement("ALTER TABLE operations MODIFY COLUMN type_operation VARCHAR(60) NOT NULL");

        Schema::table('operations', function (Blueprint $table) {
            $table->string('reference')->unique()->nullable()->after('paiement_entrant_id');
            $table->timestamp('date_operation')->nullable()->after('reference');
            $table->foreignUuid('operation_parent_id')
                ->nullable()
                ->after('date_operation')
                ->references('id')
                ->on('operations')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('operations', function (Blueprint $table) {
            $table->dropForeign(['operation_parent_id']);
            $table->dropColumn(['reference', 'date_operation', 'operation_parent_id']);
        });

        DB::statement("ALTER TABLE operations MODIFY COLUMN type_operation ENUM(
            'PRELEVEMENT_COTISATION','PRELEVEMENT_EPARGNE','RETRAIT_EPARGNE',
            'REVERSEMENT','ESCROW','REVERSEMENT_ESCROW','COMMISSION',
            'RETRAIT_COTISATION','VIREMENT'
        ) NOT NULL");
    }
};
