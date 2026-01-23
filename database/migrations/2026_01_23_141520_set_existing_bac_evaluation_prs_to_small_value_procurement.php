<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Set all PRs in bac_evaluation or bac_approved status to small_value_procurement
     * if they don't already have a procurement method set.
     */
    public function up(): void
    {
        DB::table('purchase_requests')
            ->whereIn('status', ['bac_evaluation', 'bac_approved'])
            ->whereNull('procurement_method')
            ->update([
                'procurement_method' => 'small_value_procurement',
                'procurement_method_set_at' => now(),
            ]);
    }

    /**
     * This migration is not reversible as we cannot determine which PRs
     * had their procurement_method set by this migration vs manually.
     */
    public function down(): void
    {
        // Not reversible - do nothing
    }
};
