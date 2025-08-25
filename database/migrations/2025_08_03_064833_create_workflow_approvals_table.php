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
        Schema::create('workflow_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_request_id')->constrained('purchase_requests');
            
            // Approval step info
            $table->enum('step_name', [
                'supply_office_review',
                'budget_office_earmarking', 
                'ceo_initial_approval',
                'bac_evaluation',
                'bac_award_recommendation',
                'ceo_final_approval',
                'po_generation',
                'po_approval'
            ]);
            
            $table->integer('step_order'); // 1, 2, 3, etc.
            $table->foreignId('approver_id')->constrained('users'); // Who needs to approve
            $table->foreignId('approved_by')->nullable()->constrained('users'); // Who actually approved
            
            // Approval details
            $table->enum('status', [
                'pending',
                'approved', 
                'rejected',
                'returned_for_revision',
                'skipped'
            ])->default('pending');
            
            $table->text('comments')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('assigned_at');
            $table->timestamp('responded_at')->nullable();
            $table->integer('days_to_respond')->nullable(); // SLA tracking
            
            $table->timestamps();
            
            // Indexes
            $table->index(['purchase_request_id', 'step_order']);
            $table->index(['approver_id', 'status']);
            $table->unique(['purchase_request_id', 'step_name']); // One approval per step per PR
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workflow_approvals');
    }
};
