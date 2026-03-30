<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDepartmentRequestRequest;
use App\Models\DepartmentRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DepartmentRequestController extends Controller
{
    public function create(): View
    {
        return view('auth.request-department');
    }

    public function store(StoreDepartmentRequestRequest $request): RedirectResponse
    {
        DepartmentRequest::create([
            'name' => $request->validated('name'),
            'code' => strtoupper($request->validated('code')),
            'description' => $request->validated('description'),
            'requester_email' => $request->validated('requester_email'),
            'status' => 'pending',
        ]);

        return redirect()
            ->route('register')
            ->with('status', 'Department request submitted. Once the CEO approves it, the department will appear in the registration form.');
    }
}
