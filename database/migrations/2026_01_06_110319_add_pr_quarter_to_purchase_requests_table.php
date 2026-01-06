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
            // Add pr_quarter column to track which quarter the PR was created in
            $table->integer('pr_quarter')->nullable()->after('status')->comment('Quarter when PR was created (1-4)');
            $table->index('pr_quarter');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->dropIndex(['pr_quarter']);
            $table->dropColumn('pr_quarter');
        });
    }
};
