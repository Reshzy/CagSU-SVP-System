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
        Schema::table('quotation_items', function (Blueprint $table) {
            $table->boolean('is_withdrawn')->default(false)->after('disqualification_reason');
            $table->timestamp('withdrawn_at')->nullable()->after('is_withdrawn');
            $table->text('withdrawal_reason')->nullable()->after('withdrawn_at');

            $table->index(['is_withdrawn', 'is_winner']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotation_items', function (Blueprint $table) {
            $table->dropIndex(['is_withdrawn', 'is_winner']);
            $table->dropColumn(['is_withdrawn', 'withdrawn_at', 'withdrawal_reason']);
        });
    }
};
