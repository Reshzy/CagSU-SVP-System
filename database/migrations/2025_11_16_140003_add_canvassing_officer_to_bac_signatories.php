<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE bac_signatories MODIFY COLUMN position ENUM(
            'bac_chairman',
            'bac_vice_chairman',
            'bac_member',
            'head_bac_secretariat',
            'ceo',
            'canvassing_officer'
        )");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE bac_signatories MODIFY COLUMN position ENUM(
            'bac_chairman',
            'bac_vice_chairman',
            'bac_member',
            'head_bac_secretariat',
            'ceo'
        )");
    }
};
