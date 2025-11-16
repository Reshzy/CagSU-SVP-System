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
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->timestamp('procurement_method_set_at')->nullable()->after('procurement_method');
            $table->foreignId('procurement_method_set_by')->nullable()->after('procurement_method_set_at')->constrained('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->dropForeign(['procurement_method_set_by']);
            $table->dropColumn(['procurement_method_set_at', 'procurement_method_set_by']);
        });
    }
};
