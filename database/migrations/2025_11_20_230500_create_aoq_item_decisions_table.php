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
        Schema::create('aoq_item_decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_request_id')->constrained('purchase_requests');
            $table->foreignId('purchase_request_item_id')->constrained('purchase_request_items');
            $table->foreignId('winning_quotation_item_id')->nullable()->constrained('quotation_items'); // Which item won
            $table->enum('decision_type', ['auto', 'tie_resolution', 'bac_override']); // How winner was determined
            $table->text('justification')->nullable(); // Reason for tie resolution or override
            $table->foreignId('decided_by')->nullable()->constrained('users'); // BAC officer who made decision
            $table->timestamp('decided_at')->nullable();
            $table->boolean('is_active')->default(true); // Allow history of decisions if changed
            $table->timestamps();
            
            // Ensure one active decision per item
            $table->unique(['purchase_request_item_id', 'is_active'], 'unique_active_decision');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aoq_item_decisions');
    }
};
