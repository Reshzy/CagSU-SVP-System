<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('purchase_request_items', function (Blueprint $table) {
            // Drop the old enum constraint on item_category if it exists
            // SQLite doesn't support modifying enums, so we need to handle this carefully
            
            // For SQLite: We'll need to recreate the table
            // For MySQL/PostgreSQL: We can alter the column
            
            // First, let's make item_category nullable and change it to support more values
            // Since SQLite doesn't support ALTER COLUMN well, we'll use raw SQL
            
            // Add a new temporary column
            $table->string('item_category_temp')->nullable()->after('item_category');
        });
        
        // Copy data from old column to new column
        DB::statement('UPDATE purchase_request_items SET item_category_temp = item_category');
        
        Schema::table('purchase_request_items', function (Blueprint $table) {
            // Drop the old column
            $table->dropColumn('item_category');
        });
        
        Schema::table('purchase_request_items', function (Blueprint $table) {
            // Add the new column with flexible values
            $table->string('item_category')->nullable()->after('estimated_total_cost');
        });
        
        // Copy data back
        DB::statement('UPDATE purchase_request_items SET item_category = item_category_temp');
        
        Schema::table('purchase_request_items', function (Blueprint $table) {
            // Drop the temporary column
            $table->dropColumn('item_category_temp');
            
            // Note: ppmp_item_id should already exist from migration 2025_10_19_000004
            // If not, that migration needs to be run first
            
            // Ensure item_code can handle PPMP item codes (they're longer)
            // This is already nullable and string, so it should be fine
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_request_items', function (Blueprint $table) {
            // Add temporary column with old enum values
            $table->enum('item_category_temp', [
                'office_supplies',
                'equipment',
                'materials',
                'services',
                'infrastructure',
                'ict_equipment',
                'furniture',
                'consumables',
                'other'
            ])->default('other')->after('item_category');
        });
        
        // Map current values to valid enum values
        DB::statement("UPDATE purchase_request_items SET item_category_temp = CASE 
            WHEN item_category IN ('office_supplies', 'equipment', 'materials', 'services', 'infrastructure', 'ict_equipment', 'furniture', 'consumables', 'other') 
            THEN item_category 
            ELSE 'other' 
        END");
        
        Schema::table('purchase_request_items', function (Blueprint $table) {
            // Drop the new flexible column
            $table->dropColumn('item_category');
        });
        
        Schema::table('purchase_request_items', function (Blueprint $table) {
            // Rename back
            $table->renameColumn('item_category_temp', 'item_category');
        });
    }
};
