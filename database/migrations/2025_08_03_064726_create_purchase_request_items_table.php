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
        Schema::create('purchase_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_request_id')->constrained('purchase_requests')->onDelete('cascade');
            
            // Item Details
            $table->string('item_code')->nullable(); // Internal item code if exists
            $table->string('item_name'); // Name/description of item
            $table->text('detailed_specifications'); // Technical specs, brand preferences, etc.
            $table->string('unit_of_measure'); // pcs, kg, meters, etc.
            $table->integer('quantity_requested');
            $table->decimal('estimated_unit_cost', 12, 2);
            $table->decimal('estimated_total_cost', 15, 2); // quantity * unit_cost
            
            // Classification
            $table->enum('item_category', [
                'office_supplies',
                'equipment',
                'materials',
                'services',
                'infrastructure',
                'ict_equipment',
                'furniture',
                'consumables',
                'other'
            ]);
            
            // Additional Requirements
            $table->text('special_requirements')->nullable(); // Delivery requirements, installation, etc.
            $table->date('needed_by_date')->nullable(); // Item-specific date if different from PR
            $table->boolean('is_available_locally')->default(true); // For procurement planning
            
            // Budget tracking per item
            $table->string('budget_line_item')->nullable(); // Specific budget allocation
            $table->decimal('approved_budget', 15, 2)->nullable(); // After earmarking
            
            // Status per item (some items might be rejected while others approved)
            $table->enum('item_status', [
                'pending',
                'approved',
                'rejected',
                'modified',
                'cancelled'
            ])->default('pending');
            
            $table->text('rejection_reason')->nullable();
            $table->text('modification_notes')->nullable();
            
            // Final awarded details (after BAC evaluation)
            $table->decimal('awarded_unit_price', 12, 2)->nullable();
            $table->decimal('awarded_total_price', 15, 2)->nullable();
            $table->foreignId('awarded_supplier_id')->nullable()->constrained('suppliers');
            
            $table->timestamps();
            
            // Indexes
            $table->index(['purchase_request_id', 'item_status']);
            $table->index('item_category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_request_items');
    }
};
