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
        Schema::table('purchase_request_items', function (Blueprint $table) {
            // Add quarter tracking columns
            $table->integer('ppmp_quarter')->nullable()->after('ppmp_item_id')->comment('Quarter when PR was created (1-4)');
            $table->integer('ppmp_planned_qty_for_quarter')->nullable()->after('ppmp_quarter')->comment('Planned quantity in PPMP for that quarter');
            $table->integer('ppmp_remaining_qty_at_creation')->nullable()->after('ppmp_planned_qty_for_quarter')->comment('Remaining quantity when PR was created');
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
