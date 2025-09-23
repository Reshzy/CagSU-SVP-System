<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Approve existing users with Executive Officer or System Admin roles
        // Works with spatie/permission default tables: model_has_roles and roles
        $roleIds = DB::table('roles')
            ->whereIn('name', ['Executive Officer', 'System Admin'])
            ->pluck('id');

        if ($roleIds->isEmpty()) {
            return;
        }

        $userIds = DB::table('model_has_roles')
            ->whereIn('role_id', $roleIds)
            ->where('model_type', 'App\\Models\\User')
            ->pluck('model_id');

        if ($userIds->isEmpty()) {
            return;
        }

        DB::table('users')
            ->whereIn('id', $userIds)
            ->update([
                'approval_status' => 'approved',
                'is_active' => true,
                'approved_at' => now(),
            ]);
    }

    public function down(): void
    {
        // No-op: don't revert approval automatically
    }
};


