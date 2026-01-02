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
        Schema::create('app_items', function (Blueprint $table) {
            $table->id();
            $table->year('fiscal_year'); // e.g., 2025
            $table->string('category'); // e.g., "ALCOHOL OR ACETONE BASED ANTISEPTICS"
            $table->string('item_code'); // e.g., "12191601-AL-E04"
            $table->string('item_name'); // e.g., "ALCOHOL, Ethyl, 500 mL"
            $table->string('unit_of_measure'); // e.g., "bottle"
            $table->decimal('unit_price', 15, 2); // Price per unit
            $table->text('specifications')->nullable(); // Detailed specifications
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes for performance
            $table->index('fiscal_year');
            $table->index('category');
            $table->index('is_active');
            $table->index(['fiscal_year', 'is_active']);
            $table->index(['fiscal_year', 'category']);

            // Unique constraint: one item_code per fiscal year
            $table->unique(['item_code', 'fiscal_year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_items');
    }
};
