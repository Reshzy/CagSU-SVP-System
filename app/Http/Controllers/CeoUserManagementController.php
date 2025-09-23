<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CeoUserManagementController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        // Handle reset
        if ($request->boolean('reset')) {
            $request->session()->forget('ceo.users.filters');
            return redirect()->route('ceo.users.index');
        }

        $allowedStatuses = ['pending', 'approved', 'rejected'];

        // Pull filters from query or session
        $filters = [
            'status' => $request->query('status'),
            'department_id' => $request->query('department_id'),
        ];

        if ($filters['status'] === null && $filters['department_id'] === null) {
            $filters = $request->session()->get('ceo.users.filters', [
                'status' => null,
                'department_id' => null,
            ]);
        } else {
            $request->session()->put('ceo.users.filters', $filters);
        }

        // Validate status filter
        $status = in_array($filters['status'], $allowedStatuses, true) ? $filters['status'] : null;
        $departmentId = $filters['department_id'] ?: null;

        $query = User::query()->with('department')->orderByDesc('created_at');
        if ($status) {
            $query->where('approval_status', $status);
        }
        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }

        $users = $query->paginate(20)->appends(array_filter([
            'status' => $status,
            'department_id' => $departmentId,
        ]));

        $departments = Department::orderBy('name')->get(['id','name']);

        return view('ceo.users.index', [
            'users' => $users,
            'departments' => $departments,
            'status' => $status,
            'departmentId' => $departmentId,
        ]);
    }

    public function show(User $user): View
    {
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
        return redirect()->route('ceo.users.index')->with('status', 'User approved.');
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


