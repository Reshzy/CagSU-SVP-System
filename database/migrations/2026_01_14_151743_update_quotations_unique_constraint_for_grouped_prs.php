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
        Schema::table('quotations', function (Blueprint $table) {
            // Drop the old unique constraint that prevents same supplier across different groups
            $table->dropUnique(['purchase_request_id', 'supplier_id']);
            
            // Add new unique constraint that allows same supplier in different groups
            // For non-grouped PRs (pr_item_group_id = NULL), still enforce one quote per supplier per PR
            // For grouped PRs, enforce one quote per supplier per group
            $table->unique(['purchase_request_id', 'supplier_id', 'pr_item_group_id'], 'quotations_pr_supplier_group_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            // Drop the new constraint
            $table->dropUnique('quotations_pr_supplier_group_unique');
            
            // Restore the old constraint
            $table->unique(['purchase_request_id', 'supplier_id']);
        });
    }
};
