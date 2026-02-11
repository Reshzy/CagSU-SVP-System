<?php

namespace App\Http\Controllers;

use App\Models\PoSignatory;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PoSignatoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $signatories = PoSignatory::with('user')
            ->orderBy('position')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('supply.po_signatories.index', compact('signatories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        // Get users with Supply-related roles
        $users = User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['Supply Officer', 'System Admin', 'Executive Officer']);
        })->orderBy('name')->get();

        // If no users found with roles, get all users as fallback
        if ($users->isEmpty()) {
            $users = User::orderBy('name')->get();
        }

        return view('supply.po_signatories.create', compact('users'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'input_type' => ['required', 'in:user,manual'],
            'user_id' => ['nullable', 'required_if:input_type,user', 'exists:users,id'],
            'manual_name' => ['nullable', 'required_if:input_type,manual', 'string', 'max:255'],
            'position' => ['required', 'in:ceo,chief_accountant'],
            'prefix' => ['nullable', 'string', 'max:50'],
            'suffix' => ['nullable', 'string', 'max:50'],
            'is_active' => ['boolean'],
        ]);

        // Check if there's already an active signatory for this position
        $existingActive = PoSignatory::where('position', $validated['position'])
            ->where('is_active', true)
            ->exists();

        if ($existingActive && ($validated['is_active'] ?? true)) {
            return back()
                ->withInput()
                ->with('error', 'There is already an active signatory for this position. Please deactivate the existing one first.');
        }

        PoSignatory::create([
            'user_id' => $validated['input_type'] === 'user' ? $validated['user_id'] : null,
            'manual_name' => $validated['input_type'] === 'manual' ? $validated['manual_name'] : null,
            'position' => $validated['position'],
            'prefix' => $validated['prefix'] ?? null,
            'suffix' => $validated['suffix'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()->route('supply.po-signatories.index')
            ->with('status', 'PO Signatory added successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PoSignatory $poSignatory): View
    {
        $poSignatory->load('user');

        // Get users with Supply-related roles
        $users = User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['Supply Officer', 'System Admin', 'Executive Officer']);
        })->orderBy('name')->get();

        // If no users found with roles, get all users as fallback
        if ($users->isEmpty()) {
            $users = User::orderBy('name')->get();
        }

        return view('supply.po_signatories.edit', compact('poSignatory', 'users'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PoSignatory $poSignatory): RedirectResponse
    {
        $validated = $request->validate([
            'input_type' => ['required', 'in:user,manual'],
            'user_id' => ['nullable', 'required_if:input_type,user', 'exists:users,id'],
            'manual_name' => ['nullable', 'required_if:input_type,manual', 'string', 'max:255'],
            'position' => ['required', 'in:ceo,chief_accountant'],
            'prefix' => ['nullable', 'string', 'max:50'],
            'suffix' => ['nullable', 'string', 'max:50'],
            'is_active' => ['boolean'],
        ]);

        // Check if there's already an active signatory for this position (excluding current)
        $existingActive = PoSignatory::where('position', $validated['position'])
            ->where('is_active', true)
            ->where('id', '!=', $poSignatory->id)
            ->exists();

        if ($existingActive && ($validated['is_active'] ?? $poSignatory->is_active)) {
            return back()
                ->withInput()
                ->with('error', 'There is already an active signatory for this position. Please deactivate the existing one first.');
        }

        $poSignatory->update([
            'user_id' => $validated['input_type'] === 'user' ? $validated['user_id'] : null,
            'manual_name' => $validated['input_type'] === 'manual' ? $validated['manual_name'] : null,
            'position' => $validated['position'],
            'prefix' => $validated['prefix'] ?? null,
            'suffix' => $validated['suffix'] ?? null,
            'is_active' => $validated['is_active'] ?? $poSignatory->is_active,
        ]);

        return redirect()->route('supply.po-signatories.index')
            ->with('status', 'PO Signatory updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PoSignatory $poSignatory): RedirectResponse
    {
        $poSignatory->delete();

        return redirect()->route('supply.po-signatories.index')
            ->with('status', 'PO Signatory removed successfully.');
    }
}
