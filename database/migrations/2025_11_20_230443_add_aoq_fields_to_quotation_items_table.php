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
            $table->integer('rank')->nullable()->after('remarks'); // Rank among all quotations for this item (1 = lowest/best)
            $table->boolean('is_lowest')->default(false)->after('rank'); // Is this the lowest price for this item?
            $table->boolean('is_tied')->default(false)->after('is_lowest'); // Is this tied with other quotes?
            $table->boolean('is_winner')->default(false)->after('is_tied'); // Final winner after tie resolution
            $table->text('disqualification_reason')->nullable()->after('is_winner'); // Why this quote was disqualified
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotation_items', function (Blueprint $table) {
            $table->dropColumn(['rank', 'is_lowest', 'is_tied', 'is_winner', 'disqualification_reason']);
        });
    }
};
