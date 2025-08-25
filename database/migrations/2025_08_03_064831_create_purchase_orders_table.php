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
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('po_number')->unique(); // e.g., PO-2025-0001
            $table->foreignId('purchase_request_id')->constrained('purchase_requests');
            $table->foreignId('supplier_id')->constrained('suppliers');
            $table->foreignId('quotation_id')->constrained('quotations');
            
            // PO Details
            $table->date('po_date');
            $table->decimal('total_amount', 15, 2);
            $table->text('delivery_address');
            $table->date('delivery_date_required');
            $table->text('terms_and_conditions');
            $table->text('special_instructions')->nullable();
            
            // Status tracking
            $table->enum('status', [
                'draft',
                'pending_approval',
                'approved',
                'sent_to_supplier',
                'acknowledged_by_supplier',
                'in_progress',
                'delivered',
                'completed',
                'cancelled'
            ])->default('draft');
            
            // Approval tracking
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('sent_to_supplier_at')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            
            // Delivery tracking
            $table->date('actual_delivery_date')->nullable();
            $table->text('delivery_notes')->nullable();
            $table->boolean('delivery_complete')->default(false);
            
            $table->timestamps();
            
            // Indexes
            $table->index(['status', 'po_date']);
            $table->index(['supplier_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
