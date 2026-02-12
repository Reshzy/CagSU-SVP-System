<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBatchPurchaseOrderRequest;
use App\Http\Requests\StorePurchaseOrderRequest;
use App\Models\PoSignatory;
use App\Models\PrItemGroup;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\Quotation;
use App\Models\Supplier;
use App\Services\PurchaseOrderExportService;
use App\Services\PurchaseOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PurchaseOrderController extends Controller
{
    public function index(Request $request): View
    {
        $orders = PurchaseOrder::with(['purchaseRequest', 'supplier'])
            ->latest('po_date')
            ->paginate(15);

        return view('supply.purchase_orders.index', compact('orders'));
    }

    public function create(Request $request, PurchaseRequest $purchaseRequest): View
    {
        abort_unless(in_array($purchaseRequest->status, ['bac_approved', 'bac_evaluation']), 403);

        // Check if creating PO for a specific group
        $groupId = $request->query('group');
        $itemGroup = $groupId ? PrItemGroup::find($groupId) : null;

        // Load items (all or just from the group)
        if ($itemGroup) {
            $purchaseRequest->load(['items' => function ($query) use ($itemGroup) {
                $query->where('pr_item_group_id', $itemGroup->id);
            }]);
        } else {
            $purchaseRequest->load('items');
        }

        // Get winning supplier info from AOQ item-level winners
        $poService = new PurchaseOrderService;
        $winningItemsBySupplier = $poService->getWinningItemsGroupedBySupplier($purchaseRequest, $itemGroup);

        // Get the first (and should be only) winning supplier data
        $winningSupplierData = $winningItemsBySupplier->first();
        $winningQuotation = $winningSupplierData ? $winningSupplierData['quotation'] : null;

        $suppliers = Supplier::orderBy('business_name')->get();

        // Load PO signatories
        $ceoSignatory = PoSignatory::active()->position('ceo')->first();
        $chiefAccountantSignatory = PoSignatory::active()->position('chief_accountant')->first();

        // Generate next PO number for display
        $nextPoNumber = PurchaseOrder::generateNextPoNumber();

        return view('supply.purchase_orders.create', compact(
            'purchaseRequest',
            'winningQuotation',
            'suppliers',
            'ceoSignatory',
            'chiefAccountantSignatory',
            'nextPoNumber',
            'itemGroup'
        ));
    }

    public function store(StorePurchaseOrderRequest $request, PurchaseRequest $purchaseRequest): RedirectResponse
    {
        abort_unless(in_array($purchaseRequest->status, ['bac_approved', 'bac_evaluation']), 403);

        $validated = $request->validated();

        // Determine the item group from the quotation if not provided
        $prItemGroupId = $validated['pr_item_group_id'] ?? null;
        if (! $prItemGroupId && isset($validated['quotation_id'])) {
            $quotation = \App\Models\Quotation::find($validated['quotation_id']);
            $prItemGroupId = $quotation?->pr_item_group_id;
        }

        $po = PurchaseOrder::create([
            'po_number' => PurchaseOrder::generateNextPoNumber(),
            'purchase_request_id' => $purchaseRequest->id,
            'pr_item_group_id' => $prItemGroupId,
            'supplier_id' => $validated['supplier_id'],
            'quotation_id' => $validated['quotation_id'] ?? null,
            'po_date' => now(),
            'tin' => $validated['tin'] ?? null,
            'supplier_name_override' => $validated['supplier_name_override'] ?? null,
            'funds_cluster' => $validated['funds_cluster'],
            'funds_available' => $validated['funds_available'],
            'ors_burs_no' => $validated['ors_burs_no'],
            'ors_burs_date' => $validated['ors_burs_date'],
            'total_amount' => $validated['total_amount'],
            'delivery_address' => $validated['delivery_address'],
            'delivery_date_required' => $validated['delivery_date_required'],
            'terms_and_conditions' => $validated['terms_and_conditions'],
            'special_instructions' => $validated['special_instructions'] ?? null,
            'status' => 'pending_approval',
        ]);

        // Update PR status based on group completion
        // If PR has groups, check if all groups now have POs
        if ($purchaseRequest->itemGroups->isNotEmpty()) {
            $newStatus = $purchaseRequest->allGroupsHavePo()
                ? 'po_generation'
                : 'partial_po_generation';
        } else {
            // Non-grouped PR - straight to po_generation
            $newStatus = 'po_generation';
        }

        $purchaseRequest->status = $newStatus;
        $purchaseRequest->status_updated_at = now();
        $purchaseRequest->save();

        return redirect()->route('supply.purchase-orders.show', $po)->with('status', 'Purchase Order created.');
    }

    public function show(PurchaseOrder $purchaseOrder): View
    {
        $purchaseOrder->load(['purchaseRequest', 'supplier', 'quotation']);

        return view('supply.purchase_orders.show', compact('purchaseOrder'));
    }

    public function update(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'in:send_to_supplier,acknowledge,mark_delivered,complete'],
            'notes' => ['nullable', 'string'],
            'inspection_file' => ['nullable', 'file', 'max:10240'],
        ]);

        switch ($validated['action']) {
            case 'send_to_supplier':
                $purchaseOrder->status = 'sent_to_supplier';
                $purchaseOrder->sent_to_supplier_at = now();
                // Update related PR status to supplier_processing
                if ($purchaseOrder->purchaseRequest) {
                    $purchaseOrder->purchaseRequest->status = 'supplier_processing';
                    $purchaseOrder->purchaseRequest->status_updated_at = now();
                    $purchaseOrder->purchaseRequest->save();
                }
                break;
            case 'acknowledge':
                $purchaseOrder->status = 'acknowledged_by_supplier';
                $purchaseOrder->acknowledged_at = now();
                // Keep PR in supplier_processing
                if ($purchaseOrder->purchaseRequest && $purchaseOrder->purchaseRequest->status !== 'supplier_processing') {
                    $purchaseOrder->purchaseRequest->status = 'supplier_processing';
                    $purchaseOrder->purchaseRequest->status_updated_at = now();
                    $purchaseOrder->purchaseRequest->save();
                }
                break;
            case 'mark_delivered':
                $purchaseOrder->status = 'delivered';
                $purchaseOrder->actual_delivery_date = now()->toDateString();
                // Update PR to delivered
                if ($purchaseOrder->purchaseRequest) {
                    $purchaseOrder->purchaseRequest->status = 'delivered';
                    $purchaseOrder->purchaseRequest->status_updated_at = now();
                    $purchaseOrder->purchaseRequest->save();
                }
                break;
            case 'complete':
                $purchaseOrder->status = 'completed';
                $purchaseOrder->delivery_complete = true;
                // Update PR to completed
                if ($purchaseOrder->purchaseRequest) {
                    $purchaseOrder->purchaseRequest->status = 'completed';
                    $purchaseOrder->purchaseRequest->completed_at = now();
                    $purchaseOrder->purchaseRequest->status_updated_at = now();
                    $purchaseOrder->purchaseRequest->save();
                }
                break;
        }

        // Handle optional inspection report upload
        if ($request->hasFile('inspection_file')) {
            $path = $request->file('inspection_file')->store('documents', 'public');
            \App\Models\Document::create([
                'document_number' => 'DOC-'.now()->year.'-'.str_pad((string) \App\Models\Document::max('id') + 1, 4, '0', STR_PAD_LEFT),
                'documentable_type' => \App\Models\PurchaseOrder::class,
                'documentable_id' => $purchaseOrder->id,
                'document_type' => 'inspection_report',
                'title' => 'Inspection & Acceptance Report',
                'description' => 'Uploaded upon completion',
                'file_name' => $request->file('inspection_file')->getClientOriginalName(),
                'file_path' => $path,
                'file_extension' => $request->file('inspection_file')->getClientOriginalExtension(),
                'file_size' => $request->file('inspection_file')->getSize(),
                'mime_type' => $request->file('inspection_file')->getClientMimeType(),
                'uploaded_by' => Auth::id(),
                'is_public' => false,
                'status' => 'approved',
            ]);
        }

        $purchaseOrder->save();

        return back()->with('status', 'PO updated.');
    }

    public function export(PurchaseOrder $purchaseOrder): BinaryFileResponse
    {
        $exportService = new PurchaseOrderExportService;
        $filePath = $exportService->generateExcel($purchaseOrder);

        return response()->download($filePath, 'PO-'.$purchaseOrder->po_number.'.xlsx')->deleteFileAfterSend(true);
    }

    /**
     * Preview purchase orders to be created from winning suppliers
     */
    public function preview(Request $request, PurchaseRequest $purchaseRequest): View|RedirectResponse
    {
        abort_unless(in_array($purchaseRequest->status, ['bac_approved', 'bac_evaluation']), 403);

        $groupId = $request->query('group');
        $itemGroup = $groupId ? PrItemGroup::find($groupId) : null;

        $poService = new PurchaseOrderService;
        $winningItemsBySupplier = $poService->getWinningItemsGroupedBySupplier($purchaseRequest, $itemGroup);

        // If only one supplier, redirect to single PO creation (backward compatibility)
        if ($winningItemsBySupplier->count() === 1) {
            return redirect()->route('supply.purchase-orders.create', [
                'purchaseRequest' => $purchaseRequest,
                'group' => $groupId,
            ]);
        }

        return view('supply.purchase_orders.preview', compact(
            'purchaseRequest',
            'itemGroup',
            'winningItemsBySupplier'
        ));
    }

    /**
     * Show batch creation form for multiple POs
     */
    public function batchCreate(Request $request, PurchaseRequest $purchaseRequest): View
    {
        abort_unless(in_array($purchaseRequest->status, ['bac_approved', 'bac_evaluation']), 403);

        $groupId = $request->query('group');
        $itemGroup = $groupId ? PrItemGroup::find($groupId) : null;

        $poService = new PurchaseOrderService;
        $winningItemsBySupplier = $poService->getWinningItemsGroupedBySupplier($purchaseRequest, $itemGroup);

        // Load PO signatories
        $ceoSignatory = PoSignatory::active()->position('ceo')->first();
        $chiefAccountantSignatory = PoSignatory::active()->position('chief_accountant')->first();

        return view('supply.purchase_orders.batch-create', compact(
            'purchaseRequest',
            'itemGroup',
            'winningItemsBySupplier',
            'ceoSignatory',
            'chiefAccountantSignatory'
        ));
    }

    /**
     * Store multiple purchase orders in batch
     */
    public function batchStore(StoreBatchPurchaseOrderRequest $request, PurchaseRequest $purchaseRequest): RedirectResponse
    {
        abort_unless(in_array($purchaseRequest->status, ['bac_approved', 'bac_evaluation']), 403);

        $validated = $request->validated();
        $groupId = $request->input('pr_item_group_id');
        $itemGroup = $groupId ? PrItemGroup::find($groupId) : null;

        $poService = new PurchaseOrderService;
        $createdPOs = $poService->createBatchPurchaseOrders(
            $purchaseRequest,
            $itemGroup,
            $validated['purchase_orders']
        );

        return redirect()->route('supply.purchase-requests.show', $purchaseRequest)
            ->with('status', count($createdPOs).' Purchase Orders created successfully.');
    }
}
