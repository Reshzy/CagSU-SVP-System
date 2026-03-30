<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\DepartmentRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CeoDepartmentRequestController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status', 'pending');

        $allowedStatuses = ['pending', 'approved', 'rejected', ''];

        if (! in_array($status, $allowedStatuses, true)) {
            $status = 'pending';
        }

        $query = DepartmentRequest::query()->with('reviewer')->orderByDesc('created_at');

        if ($status !== '') {
            $query->where('status', $status);
        }

        $departmentRequests = $query->paginate(20)->appends(['status' => $status]);

        $pendingCount = DepartmentRequest::where('status', 'pending')->count();

        return view('ceo.department-requests.index', compact('departmentRequests', 'status', 'pendingCount'));
    }

    public function show(DepartmentRequest $departmentRequest): View
    {
        return view('ceo.department-requests.show', compact('departmentRequest'));
    }

    public function approve(DepartmentRequest $departmentRequest, Request $request): RedirectResponse
    {
        if (! $departmentRequest->isPending()) {
            return redirect()->route('ceo.department-requests.index')
                ->with('status', 'This request has already been reviewed.');
        }

        $nameConflict = Department::where('name', $departmentRequest->name)->exists();
        $codeConflict = Department::where('code', strtoupper($departmentRequest->code))->exists();

        if ($nameConflict || $codeConflict) {
            return redirect()->route('ceo.department-requests.show', $departmentRequest)
                ->with('error', 'A department with this '.($nameConflict ? 'name' : 'code').' already exists. Edit the department list or reject this request.');
        }

        Department::create([
            'name' => $departmentRequest->name,
            'code' => strtoupper($departmentRequest->code),
            'description' => $departmentRequest->description,
            'is_active' => true,
        ]);

        $departmentRequest->update([
            'status' => 'approved',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return redirect()->route('ceo.department-requests.index')
            ->with('status', "Department \"{$departmentRequest->name}\" approved and created successfully.");
    }

    public function reject(DepartmentRequest $departmentRequest, Request $request): RedirectResponse
    {
        $request->validate([
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ]);

        if (! $departmentRequest->isPending()) {
            return redirect()->route('ceo.department-requests.index')
                ->with('status', 'This request has already been reviewed.');
        }

        $departmentRequest->update([
            'status' => 'rejected',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'rejection_reason' => $request->input('rejection_reason'),
        ]);

        return redirect()->route('ceo.department-requests.index')
            ->with('status', "Department request \"{$departmentRequest->name}\" rejected.");
    }
}
