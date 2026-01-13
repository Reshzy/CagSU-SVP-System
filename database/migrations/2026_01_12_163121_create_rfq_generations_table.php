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
        Schema::create('rfq_generations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pr_item_group_id')->constrained('pr_item_groups')->onDelete('cascade');
            $table->string('rfq_number')->unique(); // e.g., RFQ-0126-0001
            $table->foreignId('generated_by')->constrained('users'); // Who generated the RFQ
            $table->timestamp('generated_at')->nullable();
            $table->string('file_path')->nullable(); // Path to stored RFQ document
            $table->timestamps();

            // Indexes
            $table->index('pr_item_group_id');
            $table->index('rfq_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rfq_generations');
    }
};
