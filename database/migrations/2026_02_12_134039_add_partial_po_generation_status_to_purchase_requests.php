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

        DB::statement("ALTER TABLE purchase_requests MODIFY COLUMN status ENUM(
            'draft',
            'submitted',
            'supply_office_review',
            'budget_office_review',
            'ceo_approval',
            'bac_evaluation',
            'bac_approved',
            'partial_po_generation',
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
        DB::table('purchase_requests')
            ->where('status', 'partial_po_generation')
            ->update(['status' => 'po_generation']);

        if (DB::getDriverName() === 'sqlite') {
            return;
        }

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
};
