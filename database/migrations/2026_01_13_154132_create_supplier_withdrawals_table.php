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
        Schema::create('supplier_withdrawals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_item_id')->constrained('quotation_items')->onDelete('cascade');
            $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('cascade');
            $table->foreignId('purchase_request_item_id')->constrained('purchase_request_items')->onDelete('cascade');
            $table->foreignId('pr_item_group_id')->nullable()->constrained('pr_item_groups')->onDelete('set null');
            $table->text('withdrawal_reason');
            $table->timestamp('withdrawn_at');
            $table->foreignId('withdrawn_by')->constrained('users')->onDelete('restrict');
            $table->foreignId('successor_quotation_item_id')->nullable()->constrained('quotation_items')->onDelete('set null');
            $table->boolean('resulted_in_failure')->default(false);
            $table->timestamps();

            $table->index(['quotation_item_id', 'withdrawn_at']);
            $table->index(['purchase_request_item_id']);
            $table->index(['supplier_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_withdrawals');
    }
};
