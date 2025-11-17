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
        Schema::table('quotations', function (Blueprint $table) {
            // Check if column doesn't exist before adding
            if (!Schema::hasColumn('quotations', 'exceeds_abc')) {
                // Flag if any item exceeds ABC (not eligible for award)
                $table->boolean('exceeds_abc')->default(false)->after('total_amount');
            }
            
            // Note: supplier_location and quotation_file_path already exist in the original migration
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            if (Schema::hasColumn('quotations', 'exceeds_abc')) {
                $table->dropColumn('exceeds_abc');
            }
        });
    }
};

