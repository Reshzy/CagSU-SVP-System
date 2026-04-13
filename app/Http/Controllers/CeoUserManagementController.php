<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CeoUserManagementController extends Controller
{
    public function index(Request $request): View
    {
        return view('ceo.users.index');
    }

    public function show(User $user): View
    {
        $user->load(['department', 'position', 'documents']);

        return view('ceo.users.show', compact('user'));
    }

    public function approve(User $user, Request $request): RedirectResponse
    {
        $user->update([
            'approval_status' => 'approved',
            'is_active' => true,
            'approved_at' => now(),
            'approved_by' => $request->user()->id,
            'rejected_at' => null,
            'rejected_by' => null,
        ]);

        // Assign role based on position
        $this->assignRoleBasedOnPosition($user);

        return redirect()->route('ceo.users.index')->with('status', 'User approved.');
    }

    /**
     * Assign role to user based on their position
     */
    private function assignRoleBasedOnPosition(User $user): void
    {
        if (! $user->position) {
            // Default to End User if no position
            $user->assignRole('End User');

            return;
        }

        $positionName = $user->position->name;

        // Map positions to roles
        $positionRoleMap = [
            'System Administrator' => 'System Admin',
            'Supply Officer' => 'Supply Officer',
            'Budget Officer' => 'Budget Office',
            'Executive Officer' => 'Executive Officer',
            'BAC Chairman' => 'BAC Chair',
            'BAC Member' => 'BAC Members',
            'BAC Secretary' => 'BAC Secretariat',
            'Accounting Officer' => 'Accounting Office',
            'Canvassing Officer' => 'Canvassing Unit',
            'Dean' => 'Dean', // College dean
            'Employee' => 'End User', // Default for regular employees
        ];

        $roleName = $positionRoleMap[$positionName] ?? 'End User';

        // Remove any existing roles and assign the new one
        $user->syncRoles([$roleName]);
    }

    public function reject(User $user, Request $request): RedirectResponse
    {
        $user->update([
            'approval_status' => 'rejected',
            'is_active' => false,
            'rejected_at' => now(),
            'rejected_by' => $request->user()->id,
        ]);

        return redirect()->route('ceo.users.index')->with('status', 'User rejected.');
    }
}
