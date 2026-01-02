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
            // Drop the old foreign key constraint if it exists
            if (Schema::hasColumn('purchase_request_items', 'ppmp_item_id')) {
                $table->dropForeign(['ppmp_item_id']);
                $table->dropColumn('ppmp_item_id');
            }
        });

        Schema::table('purchase_request_items', function (Blueprint $table) {
            // Add the foreign key to the new ppmp_items structure
            $table->foreignId('ppmp_item_id')
                ->nullable()
                ->after('purchase_request_id')
                ->constrained('ppmp_items')
                ->onDelete('set null');

            $table->index('ppmp_item_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_request_items', function (Blueprint $table) {
            $table->dropForeign(['ppmp_item_id']);
            $table->dropColumn('ppmp_item_id');
        });
    }
};
