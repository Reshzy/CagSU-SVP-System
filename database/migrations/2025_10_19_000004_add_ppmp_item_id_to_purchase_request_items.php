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
            // Add foreign key to PPMP items (nullable for custom items)
            $table->foreignId('ppmp_item_id')->nullable()->after('purchase_request_id')->constrained('ppmp_items')->onDelete('set null');

            // Make some fields nullable since they'll come from PPMP item
            $table->string('item_name')->nullable()->change();
            $table->string('unit_of_measure')->nullable()->change();
            $table->text('detailed_specifications')->nullable()->change();

            // Add index
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

            // Revert nullable changes (assuming they were required before)
            $table->string('item_name')->nullable(false)->change();
            $table->string('unit_of_measure')->nullable(false)->change();
            $table->text('detailed_specifications')->nullable(false)->change();
        });
    }
};
