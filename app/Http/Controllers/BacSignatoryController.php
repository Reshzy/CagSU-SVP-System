<?php

namespace App\Http\Controllers;

use App\Models\BacSignatory;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BacSignatoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $signatories = BacSignatory::with('user')
            ->orderBy('position')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        // Get signatory status for validation display
        $signatoryLoader = new \App\Services\SignatoryLoaderService;
        $signatoryStatus = $signatoryLoader->getSignatoryStatus();

        return view('bac.signatories.index', compact('signatories', 'signatoryStatus'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        // Get users with BAC-related roles, including Canvassing Unit
        $bacUsers = User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['BAC Chair', 'BAC Members', 'BAC Secretariat', 'Executive Officer', 'System Admin', 'Canvassing Unit']);
        })->orderBy('name')->get();

        // If no users found with roles, get all users as fallback
        if ($bacUsers->isEmpty()) {
            $bacUsers = User::orderBy('name')->get();
        }

        return view('bac.signatories.create', compact('bacUsers'));
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
            'position' => ['required', 'in:bac_chairman,bac_vice_chairman,bac_member,head_bac_secretariat,ceo,canvassing_officer'],
            'prefix' => ['nullable', 'string', 'max:50'],
            'suffix' => ['nullable', 'string', 'max:50'],
            'is_active' => ['boolean'],
        ]);

        // Check if user-based signatory already exists for this position
        if ($validated['input_type'] === 'user' && $validated['user_id']) {
            $exists = BacSignatory::where('user_id', $validated['user_id'])
                ->where('position', $validated['position'])
                ->exists();

            if ($exists) {
                return back()
                    ->withInput()
                    ->with('error', 'This user is already assigned to this position.');
            }
        }

        BacSignatory::create([
            'user_id' => $validated['input_type'] === 'user' ? $validated['user_id'] : null,
            'manual_name' => $validated['input_type'] === 'manual' ? $validated['manual_name'] : null,
            'position' => $validated['position'],
            'prefix' => $validated['prefix'] ?? null,
            'suffix' => $validated['suffix'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()->route('bac.signatories.index')
            ->with('status', 'Signatory added successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(BacSignatory $signatory): View
    {
        $signatory->load('user');

        return view('bac.signatories.show', compact('signatory'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(BacSignatory $signatory): View
    {
        $signatory->load('user');

        // Get users with BAC-related roles, including Canvassing Unit
        $bacUsers = User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['BAC Chair', 'BAC Members', 'BAC Secretariat', 'Executive Officer', 'System Admin', 'Canvassing Unit']);
        })->orderBy('name')->get();

        // If no users found with roles, get all users as fallback
        if ($bacUsers->isEmpty()) {
            $bacUsers = User::orderBy('name')->get();
        }

        return view('bac.signatories.edit', compact('signatory', 'bacUsers'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, BacSignatory $signatory): RedirectResponse
    {
        $validated = $request->validate([
            'input_type' => ['required', 'in:user,manual'],
            'user_id' => ['nullable', 'required_if:input_type,user', 'exists:users,id'],
            'manual_name' => ['nullable', 'required_if:input_type,manual', 'string', 'max:255'],
            'position' => ['required', 'in:bac_chairman,bac_vice_chairman,bac_member,head_bac_secretariat,ceo,canvassing_officer'],
            'prefix' => ['nullable', 'string', 'max:50'],
            'suffix' => ['nullable', 'string', 'max:50'],
            'is_active' => ['boolean'],
        ]);

        // Check if user-based signatory already exists for this position (excluding current record)
        if ($validated['input_type'] === 'user' && $validated['user_id']) {
            $exists = BacSignatory::where('user_id', $validated['user_id'])
                ->where('position', $validated['position'])
                ->where('id', '!=', $signatory->id)
                ->exists();

            if ($exists) {
                return back()
                    ->withInput()
                    ->with('error', 'This user is already assigned to this position.');
            }
        }

        $signatory->update([
            'user_id' => $validated['input_type'] === 'user' ? $validated['user_id'] : null,
            'manual_name' => $validated['input_type'] === 'manual' ? $validated['manual_name'] : null,
            'position' => $validated['position'],
            'prefix' => $validated['prefix'] ?? null,
            'suffix' => $validated['suffix'] ?? null,
            'is_active' => $validated['is_active'] ?? $signatory->is_active,
        ]);

        return redirect()->route('bac.signatories.index')
            ->with('status', 'Signatory updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BacSignatory $signatory): RedirectResponse
    {
        $signatory->delete();

        return redirect()->route('bac.signatories.index')
            ->with('status', 'Signatory removed successfully.');
    }
}
