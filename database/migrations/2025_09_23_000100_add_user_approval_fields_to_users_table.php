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
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'approval_status')) {
                $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('pending')->after('is_active');
            }
            if (! Schema::hasColumn('users', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approval_status');
            }
            if (! Schema::hasColumn('users', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable()->after('approved_at');
            }
            if (! Schema::hasColumn('users', 'approved_by')) {
                $table->foreignId('approved_by')->nullable()->after('rejected_at')->constrained('users');
            }
            if (! Schema::hasColumn('users', 'rejected_by')) {
                $table->foreignId('rejected_by')->nullable()->after('approved_by')->constrained('users');
            }
        });

        // Index - only add if it doesn't already exist
        if (! Schema::hasColumn('users', 'approval_status')) {
            // If column wasn't added above, skip index
            return;
        }

        // Check if index already exists before creating
        $indexExists = false;
        try {
            $indexes = \DB::select("SHOW INDEX FROM users WHERE Key_name = 'users_approval_status_created_at_index'");
            $indexExists = ! empty($indexes);
        } catch (\Exception $e) {
            $indexExists = false;
        }

        if (! $indexExists) {
            Schema::table('users', function (Blueprint $table) {
                $table->index(['approval_status', 'created_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'approved_by')) {
                $table->dropConstrainedForeignId('approved_by');
            }
            if (Schema::hasColumn('users', 'rejected_by')) {
                $table->dropConstrainedForeignId('rejected_by');
            }
            if (Schema::hasColumn('users', 'approval_status')) {
                $table->dropIndex(['approval_status', 'created_at']);
                $table->dropColumn(['approval_status']);
            }
            if (Schema::hasColumn('users', 'approved_at')) {
                $table->dropColumn(['approved_at']);
            }
            if (Schema::hasColumn('users', 'rejected_at')) {
                $table->dropColumn(['rejected_at']);
            }
        });
    }
};
