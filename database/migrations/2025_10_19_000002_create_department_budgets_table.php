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
        Schema::create('department_budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained('departments')->onDelete('cascade');
            $table->year('fiscal_year'); // e.g., 2025
            $table->decimal('allocated_budget', 15, 2)->default(0); // Total budget allocated by budget office
            $table->decimal('utilized_budget', 15, 2)->default(0); // Budget used by completed PRs
            $table->decimal('reserved_budget', 15, 2)->default(0); // Budget reserved by pending PRs
            $table->text('notes')->nullable(); // Notes from budget officer
            $table->foreignId('set_by')->nullable()->constrained('users'); // Budget officer who set the budget
            $table->timestamps();

            // Unique constraint: one budget per department per fiscal year
            $table->unique(['department_id', 'fiscal_year']);

            // Indexes for performance
            $table->index(['department_id', 'fiscal_year']);
            $table->index('fiscal_year');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('department_budgets');
    }
};
