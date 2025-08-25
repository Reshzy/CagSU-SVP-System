<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::create('inventory_receipts', function (Blueprint $table) {
			$table->id();
			$table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
			$table->date('received_date');
			$table->string('reference_no')->nullable(); // DR No., Invoice No., etc.
			$table->enum('status', ['draft', 'posted'])->default('draft');
			$table->text('notes')->nullable();
			$table->foreignId('received_by')->constrained('users');
			$table->timestamps();
			$table->index(['purchase_order_id', 'received_date']);
		});

		Schema::create('inventory_receipt_items', function (Blueprint $table) {
			$table->id();
			$table->foreignId('inventory_receipt_id')->constrained('inventory_receipts')->cascadeOnDelete();
			$table->string('description');
			$table->string('unit_of_measure', 50)->nullable();
			$table->decimal('quantity', 12, 2)->default(0);
			$table->decimal('unit_price', 15, 2)->nullable();
			$table->decimal('total_price', 15, 2)->nullable();
			$table->timestamps();
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('inventory_receipt_items');
		Schema::dropIfExists('inventory_receipts');
	}
};


