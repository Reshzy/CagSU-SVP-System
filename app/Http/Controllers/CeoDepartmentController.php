<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CeoDepartmentController extends Controller
{
    public function index(Request $request): View
    {
        $departments = Department::orderBy('name')->paginate(20);
        return view('ceo.departments.index', compact('departments'));
    }

    public function create(): View
    {
        return view('ceo.departments.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required','string','max:255','unique:departments,name'],
            'code' => ['required','string','max:10','unique:departments,code'],
            'description' => ['nullable','string'],
            'head_name' => ['nullable','string','max:255'],
            'contact_email' => ['nullable','email','max:255'],
            'contact_phone' => ['nullable','string','max:50'],
            'is_active' => ['sometimes','boolean'],
        ]);

        $validated['is_active'] = (bool)($validated['is_active'] ?? true);

        Department::create($validated);
        return redirect()->route('ceo.departments.index')->with('status', 'Department created.');
    }

    public function edit(Department $department): View
    {
        return view('ceo.departments.edit', compact('department'));
    }

    public function update(Request $request, Department $department): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required','string','max:255', Rule::unique('departments','name')->ignore($department->id)],
            'code' => ['required','string','max:10', Rule::unique('departments','code')->ignore($department->id)],
            'description' => ['nullable','string'],
            'head_name' => ['nullable','string','max:255'],
            'contact_email' => ['nullable','email','max:255'],
            'contact_phone' => ['nullable','string','max:50'],
            'is_active' => ['sometimes','boolean'],
        ]);

        $validated['is_active'] = (bool)($validated['is_active'] ?? true);

        $department->update($validated);
        return redirect()->route('ceo.departments.index')->with('status', 'Department updated.');
    }
}


