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
        Schema::create('ppmps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained('departments')->onDelete('cascade');
            $table->year('fiscal_year'); // e.g., 2025
            $table->enum('status', ['draft', 'validated'])->default('draft');
            $table->decimal('total_estimated_cost', 15, 2)->default(0);
            $table->timestamp('validated_at')->nullable();
            $table->foreignId('validated_by')->nullable()->constrained('users');
            $table->timestamps();

            // Unique constraint: one PPMP per department per fiscal year
            $table->unique(['department_id', 'fiscal_year']);

            // Indexes for performance
            $table->index(['department_id', 'fiscal_year']);
            $table->index('fiscal_year');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ppmps');
    }
};
