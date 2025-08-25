<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::create('disbursement_vouchers', function (Blueprint $table) {
			$table->id();
			$table->string('voucher_number')->unique();
			$table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
			$table->foreignId('supplier_id')->constrained('suppliers');
			$table->decimal('amount', 15, 2);
			$table->date('voucher_date');
			$table->enum('status', [
				'draft',
				'submitted',
				'approved',
				'released',
				'paid',
				'cancelled',
			])->default('draft');
			$table->foreignId('prepared_by')->constrained('users');
			$table->foreignId('approved_by')->nullable()->constrained('users');
			$table->timestamp('approved_at')->nullable();
			$table->timestamp('released_at')->nullable();
			$table->timestamp('paid_at')->nullable();
			$table->text('remarks')->nullable();
			$table->timestamps();

			$table->index(['status', 'voucher_date']);
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('disbursement_vouchers');
	}
};


