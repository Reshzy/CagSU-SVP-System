<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_request_items', function (Blueprint $table) {
            $table->boolean('is_lot')->default(false)->after('pr_item_group_id');
            $table->string('lot_name')->nullable()->after('is_lot');
            $table->foreignId('parent_lot_id')->nullable()->after('lot_name')->constrained('purchase_request_items')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('purchase_request_items', function (Blueprint $table) {
            $table->dropForeign(['parent_lot_id']);
            $table->dropColumn(['is_lot', 'lot_name', 'parent_lot_id']);
        });
    }
};
