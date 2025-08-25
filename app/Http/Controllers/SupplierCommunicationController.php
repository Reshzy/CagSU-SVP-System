<?php

namespace App\Http\Controllers;

use App\Models\PurchaseRequest;
use App\Models\Supplier;
use App\Models\SupplierMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierCommunicationController extends Controller
{
    public function create(): View
    {
        return view('suppliers.contact');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'pr_number' => ['nullable', 'string'],
            'supplier_name' => ['nullable', 'string', 'max:255'],
            'supplier_email' => ['required', 'email', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'message_body' => ['required', 'string'],
        ]);

        $pr = null;
        if (!empty($validated['pr_number'])) {
            $pr = PurchaseRequest::where('pr_number', $validated['pr_number'])->first();
        }
        $supplier = Supplier::where('email', $validated['supplier_email'])->first();

        $msg = SupplierMessage::create([
            'purchase_request_id' => $pr?->id,
            'supplier_id' => $supplier?->id,
            'supplier_name' => $validated['supplier_name'] ?? $supplier?->business_name,
            'supplier_email' => $validated['supplier_email'],
            'subject' => $validated['subject'],
            'message_body' => $validated['message_body'],
        ]);

        // Notify Supply Officers via mail (if role exists)
        try {
            \Spatie\Permission\Models\Role::findByName('Supply Officer');
            $users = \App\Models\User::role('Supply Officer')->get();
            foreach ($users as $u) {
                $u->notify(new \App\Notifications\SupplierMessageReceived($msg));
            }
        } catch (\Throwable $e) {
            // ignore
        }

        return redirect()->route('suppliers.contact')->with('status', 'Message sent.');
    }
}


