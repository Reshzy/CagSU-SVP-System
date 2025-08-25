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
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->string('quotation_number')->unique(); // e.g., QUO-2025-0001
            $table->foreignId('purchase_request_id')->constrained('purchase_requests');
            $table->foreignId('supplier_id')->constrained('suppliers');
            
            // Quotation Details
            $table->date('quotation_date');
            $table->date('validity_date'); // Until when quote is valid
            $table->decimal('total_amount', 15, 2);
            $table->text('terms_and_conditions')->nullable();
            $table->integer('delivery_days')->nullable(); // Days to deliver
            $table->text('delivery_terms')->nullable();
            $table->text('payment_terms')->nullable();
            
            // BAC Evaluation
            $table->enum('bac_status', [
                'pending_evaluation',
                'compliant',
                'non_compliant',
                'lowest_bidder',
                'awarded',
                'not_awarded'
            ])->default('pending_evaluation');
            
            $table->decimal('technical_score', 5, 2)->nullable(); // Out of 100
            $table->decimal('financial_score', 5, 2)->nullable(); // Out of 100
            $table->decimal('total_score', 5, 2)->nullable(); // Combined score
            $table->text('bac_remarks')->nullable();
            $table->foreignId('evaluated_by')->nullable()->constrained('users');
            $table->timestamp('evaluated_at')->nullable();
            
            // Winner determination
            $table->boolean('is_winning_bid')->default(false);
            $table->text('award_justification')->nullable();
            $table->timestamp('awarded_at')->nullable();
            
            // File attachments
            $table->string('quotation_file_path')->nullable(); // PDF of quotation
            $table->string('supporting_documents')->nullable(); // JSON array of file paths
            
            $table->timestamps();
            
            // Indexes
            $table->index(['purchase_request_id', 'bac_status']);
            $table->index(['supplier_id', 'quotation_date']);
            $table->unique(['purchase_request_id', 'supplier_id']); // One quote per supplier per PR
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotations');
    }
};
