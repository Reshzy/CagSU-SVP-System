<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // This migration handles backward compatibility for existing PRs
        // Since existing PRs don't have groups, they don't need RFQ generation records
        // The system will work fine with nullable pr_item_group_id fields

        // For any PRs that have rfq_number but no groups, we leave them as-is
        // New PRs with groups will use the rfq_generations table

        // No changes needed - the nullable foreign keys handle backward compatibility
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No changes needed
    }
};
