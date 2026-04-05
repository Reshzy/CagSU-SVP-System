<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\DepartmentRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CeoDepartmentRequestController extends Controller
{
    public function index(Request $request): RedirectResponse
    {
        $status = $request->query('status', 'pending');

        $allowedStatuses = ['pending', 'approved', 'rejected', ''];

        if (! in_array($status, $allowedStatuses, true)) {
            $status = 'pending';
        }

        return redirect()->route('ceo.departments.index', [
            'tab' => 'requests',
            'status' => $status,
        ]);
    }

    public function show(DepartmentRequest $departmentRequest): View
    {
        return view('ceo.department-requests.show', compact('departmentRequest'));
    }

    public function approve(DepartmentRequest $departmentRequest, Request $request): RedirectResponse
    {
        if (! $departmentRequest->isPending()) {
            return redirect()->route('ceo.departments.index', ['tab' => 'requests'])
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
            'head_name' => $departmentRequest->head_name,
            'contact_email' => $departmentRequest->contact_email,
            'contact_phone' => $departmentRequest->contact_phone,
            'is_active' => true,
        ]);

        $departmentRequest->update([
            'status' => 'approved',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return redirect()->route('ceo.departments.index', ['tab' => 'requests'])
            ->with('status', "Department \"{$departmentRequest->name}\" approved and created successfully.");
    }

    public function reject(DepartmentRequest $departmentRequest, Request $request): RedirectResponse
    {
        $request->validate([
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ]);

        if (! $departmentRequest->isPending()) {
            return redirect()->route('ceo.departments.index', ['tab' => 'requests'])
                ->with('status', 'This request has already been reviewed.');
        }

        $departmentRequest->update([
            'status' => 'rejected',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'rejection_reason' => $request->input('rejection_reason'),
        ]);

        return redirect()->route('ceo.departments.index', ['tab' => 'requests'])
            ->with('status', "Department request \"{$departmentRequest->name}\" rejected.");
    }
}
