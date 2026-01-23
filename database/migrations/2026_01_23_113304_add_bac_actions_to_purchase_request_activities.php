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
        // Add pr_item_group_id foreign key for split PR support
        Schema::table('purchase_request_activities', function (Blueprint $table) {
            $table->foreignId('pr_item_group_id')
                ->nullable()
                ->after('user_id')
                ->constrained('pr_item_groups')
                ->onDelete('set null');

            $table->index('pr_item_group_id');
        });

        // Modify enum to add new BAC action types
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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert enum to original values
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
            'updated'
        ) NOT NULL");

        Schema::table('purchase_request_activities', function (Blueprint $table) {
            $table->dropForeign(['pr_item_group_id']);
            $table->dropIndex(['pr_item_group_id']);
            $table->dropColumn('pr_item_group_id');
        });
    }
};
