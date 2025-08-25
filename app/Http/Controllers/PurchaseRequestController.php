<?php

namespace App\Http\Controllers;

use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\Document;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use App\Notifications\PurchaseRequestSubmitted;

class PurchaseRequestController extends Controller
{
    public function index(Request $request): View
    {
        $requests = PurchaseRequest::with('department')
            ->where('requester_id', Auth::id())
            ->latest()
            ->paginate(10);

        return view('purchase_requests.index', compact('requests'));
    }

    public function create(Request $request): View
    {
        return view('purchase_requests.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'purpose' => ['required', 'string', 'max:255'],
            'justification' => ['nullable', 'string'],
            'date_needed' => ['required', 'date'],
            'priority' => ['required', 'in:low,medium,high,urgent'],
            'estimated_total' => ['required', 'numeric', 'min:0'],
            'funding_source' => ['nullable', 'string', 'max:255'],
            'budget_code' => ['nullable', 'string', 'max:255'],
            'procurement_type' => ['required', 'in:supplies_materials,equipment,infrastructure,services,consulting_services'],
            'procurement_method' => ['nullable', 'in:small_value_procurement,public_bidding,direct_contracting,negotiated_procurement'],

            // One simple item for MVP
            'item_name' => ['required', 'string', 'max:255'],
            'detailed_specifications' => ['required', 'string'],
            'unit_of_measure' => ['required', 'string', 'max:50'],
            'quantity_requested' => ['required', 'integer', 'min:1'],
            'estimated_unit_cost' => ['required', 'numeric', 'min:0'],

            // Attachments (optional)
            'attachments.*' => ['file', 'max:10240'],
        ]);

        DB::transaction(function () use ($validated, $request) {
            $purchaseRequest = PurchaseRequest::create([
                'pr_number' => PurchaseRequest::generateNextPrNumber(),
                'requester_id' => Auth::id(),
                'department_id' => optional(Auth::user()->department)->id,
                'purpose' => $validated['purpose'],
                'justification' => $validated['justification'] ?? null,
                'date_needed' => $validated['date_needed'],
                'priority' => $validated['priority'],
                'estimated_total' => $validated['estimated_total'],
                'funding_source' => $validated['funding_source'] ?? null,
                'budget_code' => $validated['budget_code'] ?? null,
                'procurement_type' => $validated['procurement_type'],
                'procurement_method' => $validated['procurement_method'] ?? null,
                'status' => 'submitted',
                'submitted_at' => now(),
                'status_updated_at' => now(),
                'current_handler_id' => null,
            ]);

            $estimatedTotal = (float)$validated['estimated_unit_cost'] * (int)$validated['quantity_requested'];

            PurchaseRequestItem::create([
                'purchase_request_id' => $purchaseRequest->id,
                'item_name' => $validated['item_name'],
                'detailed_specifications' => $validated['detailed_specifications'],
                'unit_of_measure' => $validated['unit_of_measure'],
                'quantity_requested' => $validated['quantity_requested'],
                'estimated_unit_cost' => $validated['estimated_unit_cost'],
                'estimated_total_cost' => $estimatedTotal,
                'item_category' => 'other',
            ]);

            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('documents', 'public');
                    Document::create([
                        'document_number' => self::generateNextDocumentNumber(),
                        'documentable_type' => PurchaseRequest::class,
                        'documentable_id' => $purchaseRequest->id,
                        'document_type' => 'purchase_request',
                        'title' => $file->getClientOriginalName(),
                        'description' => 'PR Attachment',
                        'file_name' => $file->getClientOriginalName(),
                        'file_path' => $path,
                        'file_extension' => $file->getClientOriginalExtension(),
                        'file_size' => $file->getSize(),
                        'mime_type' => $file->getClientMimeType(),
                        'uploaded_by' => Auth::id(),
                        'is_public' => false,
                        'status' => 'approved',
                    ]);
                }
            }

            // Notify Supply Officer role users
            try {
                \Spatie\Permission\Models\Role::findByName('Supply Officer');
                $supplyUsers = \App\Models\User::role('Supply Officer')->get();
                foreach ($supplyUsers as $user) {
                    $user->notify(new PurchaseRequestSubmitted($purchaseRequest));
                }
            } catch (\Throwable $e) {
                // silently ignore if roles not set yet
            }
        });

        return redirect()->route('purchase-requests.index')
            ->with('status', 'Purchase Request submitted successfully.');
    }

    protected static function generateNextDocumentNumber(): string
    {
        $year = now()->year;
        $prefix = 'DOC-' . $year . '-';
        $last = Document::where('document_number', 'like', $prefix . '%')
            ->orderByDesc('document_number')
            ->value('document_number');

        $nextSequence = 1;
        if ($last) {
            $parts = explode('-', $last);
            $seqStr = end($parts);
            $nextSequence = intval($seqStr) + 1;
        }

        return $prefix . str_pad((string)$nextSequence, 4, '0', STR_PAD_LEFT);
    }
}


