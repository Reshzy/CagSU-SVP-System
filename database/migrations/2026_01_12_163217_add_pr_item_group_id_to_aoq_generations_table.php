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
        Schema::table('aoq_generations', function (Blueprint $table) {
            $table->foreignId('pr_item_group_id')->nullable()->after('purchase_request_id')->constrained('pr_item_groups')->onDelete('cascade');
            $table->index('pr_item_group_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('aoq_generations', function (Blueprint $table) {
            $table->dropForeign(['pr_item_group_id']);
            $table->dropColumn('pr_item_group_id');
        });
    }
};
