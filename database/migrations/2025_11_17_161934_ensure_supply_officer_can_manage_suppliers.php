<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $permission = Permission::where('name', 'manage-suppliers')->first();
        $role = Role::where('name', 'Supply Officer')->first();

        if ($permission && $role && !$role->hasPermissionTo($permission)) {
            $role->givePermissionTo($permission);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $permission = Permission::where('name', 'manage-suppliers')->first();
        $role = Role::where('name', 'Supply Officer')->first();

        if ($permission && $role && $role->hasPermissionTo($permission)) {
            $role->revokePermissionTo($permission);
        }
    }
};
