<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\Quotation;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

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
		$purchaseRequest->load('items');
		$winningQuotation = Quotation::where('purchase_request_id', $purchaseRequest->id)
			->where('is_winning_bid', true)
			->with('supplier')
			->first();
		$suppliers = Supplier::orderBy('business_name')->get();
		return view('supply.purchase_orders.create', compact('purchaseRequest', 'winningQuotation', 'suppliers'));
	}

	public function store(Request $request, PurchaseRequest $purchaseRequest): RedirectResponse
	{
		abort_unless(in_array($purchaseRequest->status, ['bac_approved', 'bac_evaluation']), 403);

		$validated = $request->validate([
			'supplier_id' => ['required', 'exists:suppliers,id'],
			'quotation_id' => ['nullable', 'exists:quotations,id'],
			'po_date' => ['required', 'date'],
			'total_amount' => ['required', 'numeric', 'min:0'],
			'delivery_address' => ['required', 'string'],
			'delivery_date_required' => ['required', 'date', 'after_or_equal:po_date'],
			'terms_and_conditions' => ['required', 'string'],
			'special_instructions' => ['nullable', 'string'],
		]);

		$po = PurchaseOrder::create([
			'po_number' => PurchaseOrder::generateNextPoNumber(),
			'purchase_request_id' => $purchaseRequest->id,
			'supplier_id' => $validated['supplier_id'],
			'quotation_id' => $validated['quotation_id'] ?? null,
			'po_date' => $validated['po_date'],
			'total_amount' => $validated['total_amount'],
			'delivery_address' => $validated['delivery_address'],
			'delivery_date_required' => $validated['delivery_date_required'],
			'terms_and_conditions' => $validated['terms_and_conditions'],
			'special_instructions' => $validated['special_instructions'] ?? null,
			'status' => 'pending_approval',
		]);

		// Optionally update PR status to po_generation
		$purchaseRequest->status = 'po_generation';
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
				break;
			case 'acknowledge':
				$purchaseOrder->status = 'acknowledged_by_supplier';
				$purchaseOrder->acknowledged_at = now();
				break;
			case 'mark_delivered':
				$purchaseOrder->status = 'delivered';
				$purchaseOrder->actual_delivery_date = now()->toDateString();
				break;
			case 'complete':
				$purchaseOrder->status = 'completed';
				$purchaseOrder->delivery_complete = true;
				break;
		}

		// Handle optional inspection report upload
		if ($request->hasFile('inspection_file')) {
			$path = $request->file('inspection_file')->store('documents', 'public');
			\App\Models\Document::create([
				'document_number' => 'DOC-' . now()->year . '-' . str_pad((string)\App\Models\Document::max('id') + 1, 4, '0', STR_PAD_LEFT),
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
				'uploaded_by' => auth()->id(),
				'is_public' => false,
				'status' => 'approved',
			]);
				break;
		}

		$purchaseOrder->save();

		return back()->with('status', 'PO updated.');
	}
}


