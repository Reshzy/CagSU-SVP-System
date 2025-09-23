<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Department;
use App\Models\Document;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        $departments = Department::orderBy('name')->get(['id','name']);
        return view('auth.register', compact('departments'));
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'department_id' => ['nullable', 'exists:departments,id'],
            'employee_id' => ['nullable', 'string', 'max:255'],
            'position' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'id_proof' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,doc,docx']
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'department_id' => $validated['department_id'] ?? null,
            'employee_id' => $validated['employee_id'] ?? null,
            'position' => $validated['position'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'is_active' => false,
            'approval_status' => 'pending',
        ]);

        // Attach uploaded ID proof as a Document
        if ($request->hasFile('id_proof')) {
            $file = $request->file('id_proof');
            $storedPath = $file->store('user-id-proofs/'.date('Y/m'), 'public');

            $documentNumber = Document::generateNextDocumentNumber();

            $user->documents()->create([
                'document_number' => $documentNumber,
                'document_type' => 'other',
                'title' => 'University ID Proof',
                'description' => 'User-submitted identification for account approval',
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $storedPath,
                'file_extension' => $file->getClientOriginalExtension(),
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType() ?: $file->getClientMimeType(),
                'uploaded_by' => $user->id,
                'is_public' => false,
                'visible_to_roles' => ['Executive Officer','System Admin'],
                'status' => 'pending_review',
            ]);
        }

        event(new Registered($user));

        // Do not auto-login; require CEO approval
        return redirect()->route('login')->with('status', 'Registration submitted. Await CEO approval.');
    }
}
