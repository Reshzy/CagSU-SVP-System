<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            // Return workflow fields
            $table->foreignId('returned_by')->nullable()->after('rejected_by')->constrained('users');
            $table->timestamp('returned_at')->nullable()->after('rejected_at');
            $table->text('return_remarks')->nullable()->after('rejection_reason');

            // PR replacement tracking
            $table->foreignId('replaces_pr_id')->nullable()->after('id')->constrained('purchase_requests')->onDelete('set null');
            $table->foreignId('replaced_by_pr_id')->nullable()->after('replaces_pr_id')->constrained('purchase_requests')->onDelete('set null');

            // Indexes for performance
            $table->index('returned_by');
            $table->index('replaces_pr_id');
            $table->index('replaced_by_pr_id');
        });

        // Add 'returned_by_supply' to status enum
        // Using raw SQL for enum modification (works with MySQL)
        DB::statement("ALTER TABLE purchase_requests MODIFY COLUMN status ENUM(
            'draft',
            'submitted',
            'supply_office_review',
            'budget_office_review',
            'ceo_approval',
            'bac_evaluation',
            'bac_approved',
            'po_generation',
            'po_approved',
            'supplier_processing',
            'delivered',
            'completed',
            'cancelled',
            'rejected',
            'returned_by_supply'
        ) NOT NULL DEFAULT 'draft'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->dropForeign(['returned_by']);
            $table->dropForeign(['replaces_pr_id']);
            $table->dropForeign(['replaced_by_pr_id']);

            $table->dropIndex(['returned_by']);
            $table->dropIndex(['replaces_pr_id']);
            $table->dropIndex(['replaced_by_pr_id']);

            $table->dropColumn([
                'returned_by',
                'returned_at',
                'return_remarks',
                'replaces_pr_id',
                'replaced_by_pr_id',
            ]);
        });

        // Revert status enum to original values
        DB::statement("ALTER TABLE purchase_requests MODIFY COLUMN status ENUM(
            'draft',
            'submitted',
            'supply_office_review',
            'budget_office_review',
            'ceo_approval',
            'bac_evaluation',
            'bac_approved',
            'po_generation',
            'po_approved',
            'supplier_processing',
            'delivered',
            'completed',
            'cancelled',
            'rejected'
        ) NOT NULL DEFAULT 'draft'");
    }
};
