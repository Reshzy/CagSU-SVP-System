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
        // Simply make user_id nullable and add manual_name field
        // The previous attempts already dropped constraints, so we just need to finish the job
        
        Schema::table('bac_signatories', function (Blueprint $table) {
            // Make user_id nullable
            $table->unsignedBigInteger('user_id')->nullable()->change();
        });
        
        // Re-add foreign key constraint if it doesn't exist
        $foreignKeys = \DB::select("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE 
                                    WHERE TABLE_NAME = 'bac_signatories' 
                                    AND COLUMN_NAME = 'user_id' 
                                    AND CONSTRAINT_NAME LIKE '%foreign%'");
        
        if (empty($foreignKeys)) {
            Schema::table('bac_signatories', function (Blueprint $table) {
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }
        
        // Add column if it doesn't exist
        if (!Schema::hasColumn('bac_signatories', 'manual_name')) {
            Schema::table('bac_signatories', function (Blueprint $table) {
                $table->string('manual_name')->nullable()->after('user_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bac_signatories', function (Blueprint $table) {
            // Drop manual_name column
            $table->dropColumn('manual_name');
            
            // Drop foreign key
            $table->dropForeign(['user_id']);
            
            // Make user_id not nullable
            $table->foreignId('user_id')->nullable(false)->change();
            
            // Re-add foreign key
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            // Restore unique constraint
            $table->unique(['user_id', 'position']);
        });
    }
};
