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
        Schema::table('ppmp_items', function (Blueprint $table) {
            // Add college_id (references departments table which now stores colleges)
            $table->foreignId('college_id')->nullable()->after('id')->constrained('departments')->onDelete('cascade');
            $table->index('college_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ppmp_items', function (Blueprint $table) {
            $table->dropForeign(['college_id']);
            $table->dropIndex(['college_id']);
            $table->dropColumn('college_id');
        });
    }
};
