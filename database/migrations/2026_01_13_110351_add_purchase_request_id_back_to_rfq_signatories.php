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
        // First, make rfq_generation_id nullable since we now support both patterns
        Schema::table('rfq_signatories', function (Blueprint $table) {
            $table->foreignId('rfq_generation_id')->nullable()->change();
        });

        // Then add purchase_request_id as nullable
        Schema::table('rfq_signatories', function (Blueprint $table) {
            $table->foreignId('purchase_request_id')->nullable()->after('id')->constrained()->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove the purchase_request_id column
        Schema::table('rfq_signatories', function (Blueprint $table) {
            $table->dropForeign(['purchase_request_id']);
            $table->dropColumn('purchase_request_id');
        });

        // Make rfq_generation_id NOT NULL again
        Schema::table('rfq_signatories', function (Blueprint $table) {
            $table->foreignId('rfq_generation_id')->nullable(false)->change();
        });
    }
};
