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
        // Add is_archived flag to departments
        Schema::table('departments', function (Blueprint $table) {
            $table->boolean('is_archived')->default(false)->after('is_active');
            $table->timestamp('archived_at')->nullable()->after('is_archived');
            $table->index('is_archived');
        });

        // Add is_archived flag to users
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_archived')->default(false)->after('is_active');
            $table->timestamp('archived_at')->nullable()->after('is_archived');
            $table->index('is_archived');
        });

        // Add is_archived flag to purchase_requests
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->boolean('is_archived')->default(false)->after('status');
            $table->timestamp('archived_at')->nullable()->after('is_archived');
            $table->index('is_archived');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropIndex(['is_archived']);
            $table->dropColumn(['is_archived', 'archived_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['is_archived']);
            $table->dropColumn(['is_archived', 'archived_at']);
        });

        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->dropIndex(['is_archived']);
            $table->dropColumn(['is_archived', 'archived_at']);
        });
    }
};
