<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // For MySQL, we need to use raw SQL to modify the enum
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
        // Remove canvassing_officer from the enum
        DB::statement("ALTER TABLE bac_signatories MODIFY COLUMN position ENUM(
            'bac_chairman',
            'bac_vice_chairman',
            'bac_member',
            'head_bac_secretariat',
            'ceo'
        )");
    }
};

