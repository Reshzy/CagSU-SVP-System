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
        DB::statement("ALTER TABLE aoq_item_decisions MODIFY decision_type ENUM('auto', 'tie_resolution', 'bac_override', 'withdrawal_succession') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE aoq_item_decisions MODIFY decision_type ENUM('auto', 'tie_resolution', 'bac_override') NOT NULL");
    }
};
