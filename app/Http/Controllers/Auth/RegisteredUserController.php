<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Document;
use App\Models\Position;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        $departments = Department::orderBy('name')->get(['id', 'name']);
        $positions = Position::orderBy('name')->get(['id', 'name']);

        $defaultPositionId = Position::query()
            ->where('name', 'Employee')
            ->value('id');

        return view('auth.register', compact('departments', 'positions', 'defaultPositionId'));
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'department_id' => ['required', 'exists:departments,id'],
            'employee_id' => ['nullable', 'string', 'max:255'],
            'position_id' => ['required', 'exists:positions,id'],
            'phone' => ['nullable', 'string', 'max:50'],
            'id_proof' => ['required', 'array', 'min:1'],
            'id_proof.*' => ['required', 'image', 'max:10240'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'department_id' => $validated['department_id'],
            'employee_id' => $validated['employee_id'] ?? null,
            'position_id' => $validated['position_id'],
            'phone' => $validated['phone'] ?? null,
            'is_active' => false,
            'approval_status' => 'pending',
        ]);

        // Attach uploaded ID images as Documents
        foreach ($request->file('id_proof', []) as $index => $file) {
            if (! $file) {
                continue;
            }

            $storedPath = $file->store('user-id-proofs/'.date('Y/m'), 'public');
            $documentNumber = Document::generateNextDocumentNumber();

            $user->documents()->create([
                'document_number' => $documentNumber,
                'document_type' => 'other',
                'title' => 'University ID Image '.($index + 1),
                'description' => 'User-submitted identification image for account approval',
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $storedPath,
                'file_extension' => $file->getClientOriginalExtension(),
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType() ?: $file->getClientMimeType(),
                'uploaded_by' => $user->id,
                'is_public' => false,
                'visible_to_roles' => ['Executive Officer', 'System Admin'],
                'status' => 'pending_review',
            ]);
        }

        event(new Registered($user));

        // Do not auto-login; require CEO approval
        return redirect()->route('login')->with('status', 'Registration submitted. Await CEO approval.');
    }
}
