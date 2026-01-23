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
        $hasPpmpQuarter = Schema::hasColumn('purchase_request_items', 'ppmp_quarter');
        $hasPpmpPlannedQty = Schema::hasColumn('purchase_request_items', 'ppmp_planned_qty_for_quarter');
        $hasPpmpRemainingQty = Schema::hasColumn('purchase_request_items', 'ppmp_remaining_qty_at_creation');

        Schema::table('purchase_request_items', function (Blueprint $table) use ($hasPpmpQuarter, $hasPpmpPlannedQty, $hasPpmpRemainingQty) {
            // Add quarter tracking columns only if they don't exist
            if (! $hasPpmpQuarter) {
                $table->integer('ppmp_quarter')->nullable()->after('ppmp_item_id')->comment('Quarter when PR was created (1-4)');
            }
            if (! $hasPpmpPlannedQty) {
                $table->integer('ppmp_planned_qty_for_quarter')->nullable()->after('ppmp_quarter')->comment('Planned quantity in PPMP for that quarter');
            }
            if (! $hasPpmpRemainingQty) {
                $table->integer('ppmp_remaining_qty_at_creation')->nullable()->after('ppmp_planned_qty_for_quarter')->comment('Remaining quantity when PR was created');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_request_items', function (Blueprint $table) {
            $table->dropColumn([
                'ppmp_quarter',
                'ppmp_planned_qty_for_quarter',
                'ppmp_remaining_qty_at_creation',
            ]);
        });
    }
};
