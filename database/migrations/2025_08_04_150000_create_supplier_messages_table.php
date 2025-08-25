<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_request_id')->nullable()->constrained('purchase_requests')->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->string('supplier_name')->nullable();
            $table->string('supplier_email');
            $table->string('subject');
            $table->text('message_body');
            $table->enum('status', ['new','read','archived'])->default('new');
            $table->timestamps();

            $table->index(['supplier_email','created_at']);
            $table->index(['purchase_request_id','created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_messages');
    }
};


