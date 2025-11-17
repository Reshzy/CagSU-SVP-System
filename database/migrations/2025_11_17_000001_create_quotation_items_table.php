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
        Schema::create('quotation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained('quotations')->onDelete('cascade');
            $table->foreignId('purchase_request_item_id')->constrained('purchase_request_items')->onDelete('cascade');
            
            // Pricing Details
            $table->decimal('unit_price', 12, 2)->nullable(); // Nullable - supplier may not quote all items
            $table->decimal('total_price', 15, 2)->default(0); // quantity × unit_price
            
            // ABC Compliance
            $table->boolean('is_within_abc')->default(true); // true if unit_price <= ABC
            
            // Additional Info
            $table->text('remarks')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['quotation_id', 'purchase_request_item_id']);
            
            // Ensure one quote per item per quotation
            $table->unique(['quotation_id', 'purchase_request_item_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotation_items');
    }
};

