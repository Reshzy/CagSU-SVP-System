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
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE purchase_request_activities MODIFY COLUMN action ENUM(
                'created',
                'submitted',
                'status_changed',
                'returned',
                'rejected',
                'approved',
                'replacement_created',
                'notes_added',
                'assigned',
                'updated',
                'resolution_generated',
                'resolution_regenerated',
                'rfq_generated',
                'quotation_submitted',
                'quotation_evaluated',
                'aoq_generated',
                'tie_resolved',
                'bac_override',
                'supplier_withdrawal',
                'item_groups_created',
                'item_groups_updated',
                'earmark_amended'
            ) NOT NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE purchase_request_activities MODIFY COLUMN action ENUM(
                'created',
                'submitted',
                'status_changed',
                'returned',
                'rejected',
                'approved',
                'replacement_created',
                'notes_added',
                'assigned',
                'updated',
                'resolution_generated',
                'resolution_regenerated',
                'rfq_generated',
                'quotation_submitted',
                'quotation_evaluated',
                'aoq_generated',
                'tie_resolved',
                'bac_override',
                'supplier_withdrawal',
                'item_groups_created',
                'item_groups_updated'
            ) NOT NULL");
        }
    }
};
