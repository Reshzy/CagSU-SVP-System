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
        Schema::table('rfq_signatories', function (Blueprint $table) {
            // Drop foreign key first, then unique constraint, then column
            $table->dropForeign(['purchase_request_id']);
        });

        Schema::table('rfq_signatories', function (Blueprint $table) {
            $table->dropUnique(['purchase_request_id', 'position']);
        });

        Schema::table('rfq_signatories', function (Blueprint $table) {
            $table->dropColumn('purchase_request_id');

            // Add new foreign key to rfq_generations
            $table->foreignId('rfq_generation_id')->after('id')->constrained('rfq_generations')->onDelete('cascade');
            $table->unique(['rfq_generation_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rfq_signatories', function (Blueprint $table) {
            // Drop new foreign key first
            $table->dropForeign(['rfq_generation_id']);
        });

        Schema::table('rfq_signatories', function (Blueprint $table) {
            $table->dropUnique(['rfq_generation_id', 'position']);
        });

        Schema::table('rfq_signatories', function (Blueprint $table) {
            $table->dropColumn('rfq_generation_id');

            // Restore old foreign key
            $table->foreignId('purchase_request_id')->after('id')->constrained()->onDelete('cascade');
            $table->unique(['purchase_request_id', 'position']);
        });
    }
};
