<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Map existing position text values to position_id foreign keys
        $positionMappings = [
            'System Administrator' => 'System Administrator',
            'Supply Officer' => 'Supply Officer',
            'Employee' => 'Employee',
            'Budget Officer' => 'Budget Officer',
            'Executive Officer' => 'Executive Officer',
            'BAC Chairman' => 'BAC Chairman',
            'BAC Member' => 'BAC Member',
            'BAC Secretary' => 'BAC Secretary',
            'Accounting Officer' => 'Accounting Officer',
            'Canvassing Officer' => 'Canvassing Officer',
        ];

        foreach ($positionMappings as $oldPosition => $newPosition) {
            // Get the position_id from the positions table
            $position = DB::table('positions')->where('name', $newPosition)->first();
            
            if ($position) {
                // Update users with matching position text to use position_id
                DB::table('users')
                    ->where('position', $oldPosition)
                    ->update(['position_id' => $position->id]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert position_id back to null (cannot restore exact text as that data is preserved in position column)
        DB::table('users')->update(['position_id' => null]);
    }
};
