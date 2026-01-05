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
        // Drop foreign key constraint from purchase_request_items if it exists
        if (Schema::hasTable('purchase_request_items') && Schema::hasColumn('purchase_request_items', 'ppmp_item_id')) {
            try {
                Schema::table('purchase_request_items', function (Blueprint $table) {
                    $table->dropForeign(['ppmp_item_id']);
                });
            } catch (\Exception $e) {
                // Foreign key might not exist or already dropped, continue
            }
        }

        // Drop the old ppmp_items table
        Schema::dropIfExists('ppmp_items');

        // Create the new ppmp_items table with updated structure
        Schema::create('ppmp_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ppmp_id')->constrained('ppmps')->onDelete('cascade');
            $table->foreignId('app_item_id')->constrained('app_items')->onDelete('cascade');
            
            // Quarterly quantities
            $table->integer('q1_quantity')->default(0);
            $table->integer('q2_quantity')->default(0);
            $table->integer('q3_quantity')->default(0);
            $table->integer('q4_quantity')->default(0);
            $table->integer('total_quantity')->default(0);
            
            // Cost information
            $table->decimal('estimated_unit_cost', 15, 2);
            $table->decimal('estimated_total_cost', 15, 2);
            
            $table->timestamps();

            // Indexes for performance
            $table->index('ppmp_id');
            $table->index('app_item_id');
            
            // Unique constraint: one app_item per ppmp
            $table->unique(['ppmp_id', 'app_item_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ppmp_items');
        
        // Recreate old structure for rollback
        Schema::create('ppmp_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('college_id')->nullable()->constrained('departments')->onDelete('cascade');
            $table->string('category');
            $table->string('item_code')->unique();
            $table->string('item_name');
            $table->string('unit_of_measure');
            $table->decimal('unit_price', 15, 2);
            $table->text('specifications')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('category');
            $table->index('is_active');
            $table->index(['category', 'is_active']);
            $table->index('college_id');
        });
    }
};
