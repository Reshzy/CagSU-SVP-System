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
        Schema::create('purchase_requests', function (Blueprint $table) {
            $table->id();
            $table->string('pr_number')->unique(); // Auto-generated PR control number (e.g., PR-2025-0001)
            
            // Basic Information
            $table->foreignId('requester_id')->constrained('users'); // End user who submitted
            $table->foreignId('department_id')->constrained('departments');
            $table->string('purpose'); // Purpose of procurement
            $table->text('justification')->nullable(); // Why this is needed
            $table->date('date_needed'); // When items are needed
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            
            // Budget Information  
            $table->decimal('estimated_total', 15, 2); // Total estimated cost
            $table->string('funding_source')->nullable(); // Where money comes from
            $table->string('budget_code')->nullable(); // Budget allocation code
            
            // Procurement Type
            $table->enum('procurement_type', [
                'supplies_materials', 
                'equipment', 
                'infrastructure', 
                'services',
                'consulting_services'
            ]);
            $table->enum('procurement_method', [
                'small_value_procurement', // Under 50k
                'public_bidding',          // 50k and above
                'direct_contracting',
                'negotiated_procurement'
            ])->nullable();
            
            // Workflow Status - matches your 6-step process
            $table->enum('status', [
                'draft',                    // Being prepared
                'submitted',               // Step 1: Submitted for PR control number
                'supply_office_review',    // Step 1: Supply office processing 
                'budget_office_review',    // Step 2: Budget office earmarking
                'ceo_approval',           // Step 2: CEO approval needed
                'bac_evaluation',         // Step 3: BAC evaluation and quotations
                'bac_approved',           // Step 3: BAC completed, abstract ready
                'po_generation',          // Step 4: Creating purchase order
                'po_approved',            // Step 4: PO approved by CEO
                'supplier_processing',    // Step 5: With supplier for delivery
                'delivered',              // Step 5: Items delivered
                'completed',              // Step 6: Items distributed to end user
                'cancelled',              // Cancelled at any stage
                'rejected'                // Rejected (with reason)
            ])->default('draft');
            
            // Current Processing Info
            $table->foreignId('current_handler_id')->nullable()->constrained('users'); // Who's handling it now
            $table->text('current_step_notes')->nullable(); // Notes from current step
            $table->timestamp('status_updated_at')->nullable();
            
            // PPMP Information (Project Procurement Management Plan)
            $table->boolean('has_ppmp')->default(false);
            $table->string('ppmp_reference')->nullable();
            
            // Completion tracking
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('total_processing_days')->nullable(); // Track efficiency
            
            // Rejection/Cancellation
            $table->text('rejection_reason')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users');
            $table->timestamp('rejected_at')->nullable();
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['status', 'created_at']);
            $table->index(['requester_id', 'created_at']);
            $table->index(['department_id', 'status']);
            $table->index('pr_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_requests');
    }
};
