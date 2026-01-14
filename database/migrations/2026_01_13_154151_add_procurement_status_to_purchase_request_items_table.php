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
        Schema::table('purchase_request_items', function (Blueprint $table) {
            $table->enum('procurement_status', ['pending', 'awarded', 'failed', 're_pr_created'])
                ->default('pending')
                ->after('item_status');
            $table->timestamp('failed_at')->nullable()->after('procurement_status');
            $table->text('failure_reason')->nullable()->after('failed_at');
            $table->foreignId('replacement_pr_id')
                ->nullable()
                ->after('failure_reason')
                ->constrained('purchase_requests')
                ->onDelete('set null');

            $table->index(['procurement_status']);
            $table->index(['replacement_pr_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_request_items', function (Blueprint $table) {
            $table->dropForeign(['replacement_pr_id']);
            $table->dropIndex(['procurement_status']);
            $table->dropIndex(['replacement_pr_id']);
            $table->dropColumn(['procurement_status', 'failed_at', 'failure_reason', 'replacement_pr_id']);
        });
    }
};
