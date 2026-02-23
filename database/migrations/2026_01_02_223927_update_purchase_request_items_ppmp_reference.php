<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // On SQLite the column is already a plain integer — no re-creation needed
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        // Drop existing ppmp_item_id column if it exists (re-creating with correct FK target)
        if (Schema::hasColumn('purchase_request_items', 'ppmp_item_id')) {
            try {
                $foreignKeys = DB::select("
                    SELECT CONSTRAINT_NAME
                    FROM information_schema.KEY_COLUMN_USAGE
                    WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = 'purchase_request_items'
                    AND COLUMN_NAME = 'ppmp_item_id'
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                ");

                if (count($foreignKeys) > 0) {
                    Schema::table('purchase_request_items', function (Blueprint $table) {
                        $table->dropForeign(['ppmp_item_id']);
                    });
                }

                Schema::table('purchase_request_items', function (Blueprint $table) {
                    $table->dropColumn('ppmp_item_id');
                });
            } catch (\Exception $e) {
                // Column doesn't exist or FK already dropped — continue
            }
        }

        Schema::table('purchase_request_items', function (Blueprint $table) {
            $table->foreignId('ppmp_item_id')
                ->nullable()
                ->after('purchase_request_id')
                ->constrained('ppmp_items')
                ->onDelete('set null');

            $table->index('ppmp_item_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_request_items', function (Blueprint $table) {
            $table->dropForeign(['ppmp_item_id']);
            $table->dropColumn('ppmp_item_id');
        });
    }
};
