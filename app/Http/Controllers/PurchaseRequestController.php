<?php

namespace App\Http\Controllers;

use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\Document;
use App\Models\PpmpItem;
use App\Models\DepartmentBudget;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use App\Notifications\PurchaseRequestSubmitted;
use App\Services\WorkflowRouter;

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
        $user = Auth::user();

        // Get PPMP items grouped by category
        $ppmpCategories = PpmpItem::active()
            ->select('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        $ppmpItems = PpmpItem::active()
            ->orderBy('category')
            ->orderBy('item_name')
            ->get()
            ->groupBy('category');

        // Get department budget information
        $fiscalYear = date('Y');
        $departmentBudget = null;

        if ($user->department_id) {
            $departmentBudget = DepartmentBudget::getOrCreateForDepartment($user->department_id, $fiscalYear);
        }

        return view('purchase_requests.create', [
            'ppmpCategories' => $ppmpCategories,
            'ppmpItems' => $ppmpItems,
            'departmentBudget' => $departmentBudget,
            'fiscalYear' => $fiscalYear,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'purpose' => ['required', 'string', 'max:255'],
            'justification' => ['nullable', 'string'],

            // Multiple items from PPMP or custom
            'items' => ['required', 'array', 'min:1'],
            'items.*.ppmp_item_id' => ['nullable', 'exists:ppmp_items,id'],
            'items.*.item_name' => ['required', 'string', 'max:255'],
            'items.*.detailed_specifications' => ['nullable', 'string'],
            'items.*.unit_of_measure' => ['required', 'string', 'max:50'],
            'items.*.quantity_requested' => ['required', 'integer', 'min:1'],
            'items.*.estimated_unit_cost' => ['required', 'numeric', 'min:0'],

            // Attachments (optional)
            'attachments.*' => ['file', 'max:10240'],
        ]);

        $user = Auth::user();

        // Calculate total cost
        $totalCost = 0;
        foreach ($validated['items'] as $item) {
            $totalCost += (float)$item['estimated_unit_cost'] * (int)$item['quantity_requested'];
        }

        // Check budget availability
        if ($user->department_id) {
            $fiscalYear = date('Y');
            $budget = DepartmentBudget::getOrCreateForDepartment($user->department_id, $fiscalYear);

            if (!$budget->canReserve($totalCost)) {
                return back()
                    ->withInput()
                    ->withErrors(['budget' => 'Insufficient budget. Available: ₱' . number_format($budget->getAvailableBudget(), 2) . ', Required: ₱' . number_format($totalCost, 2)]);
            }
        }

        DB::transaction(function () use ($validated, $request, $totalCost, $user) {
            $purchaseRequest = PurchaseRequest::create([
                'pr_number' => PurchaseRequest::generateNextPrNumber(),
                'requester_id' => Auth::id(),
                'department_id' => $user->department_id,
                'purpose' => $validated['purpose'],
                'justification' => $validated['justification'] ?? null,
                'date_needed' => null, // Will be filled by Budget Office
                'estimated_total' => $totalCost,
                'funding_source' => null, // Will be filled by Budget Office
                'budget_code' => null, // Will be filled by Budget Office
                'procurement_type' => null, // Will be filled by Budget Office
                'procurement_method' => null, // Will be filled by Budget Office
                'status' => 'submitted',
                'submitted_at' => now(),
                'status_updated_at' => now(),
                'current_handler_id' => null,
                'has_ppmp' => true,
            ]);

            // Create items
            foreach ($validated['items'] as $itemData) {
                $estimatedTotal = (float)$itemData['estimated_unit_cost'] * (int)$itemData['quantity_requested'];

                PurchaseRequestItem::create([
                    'purchase_request_id' => $purchaseRequest->id,
                    'ppmp_item_id' => $itemData['ppmp_item_id'] ?? null,
                    'item_name' => $itemData['item_name'] ?? null,
                    'detailed_specifications' => $itemData['detailed_specifications'] ?? null,
                    'unit_of_measure' => $itemData['unit_of_measure'] ?? null,
                    'quantity_requested' => $itemData['quantity_requested'],
                    'estimated_unit_cost' => $itemData['estimated_unit_cost'],
                    'estimated_total_cost' => $estimatedTotal,
                    'item_category' => 'ppmp',
                ]);
            }

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

                // Create pending approval for Supply step
                WorkflowRouter::createPendingForRole($purchaseRequest, 'supply_office_review', 'Supply Officer');
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
