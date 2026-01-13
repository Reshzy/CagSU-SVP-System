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
        Schema::create('pr_item_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_request_id')->constrained('purchase_requests')->onDelete('cascade');
            $table->string('group_name'); // e.g., "Office Supplies", "IT Equipment"
            $table->string('group_code'); // e.g., "G1", "G2", "G3"
            $table->integer('display_order')->default(0); // For ordering groups
            $table->timestamps();

            // Indexes
            $table->index(['purchase_request_id', 'display_order']);
            $table->unique(['purchase_request_id', 'group_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pr_item_groups');
    }
};
