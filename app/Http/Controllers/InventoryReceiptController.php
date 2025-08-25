<?php

namespace App\Http\Controllers;

use App\Models\InventoryReceipt;
use App\Models\InventoryReceiptItem;
use App\Models\PurchaseOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryReceiptController extends Controller
{
	public function index(Request $request): View
	{
		$receipts = InventoryReceipt::with(['purchaseOrder'])
			->latest('received_date')
			->paginate(15);
		return view('supply.inventory_receipts.index', compact('receipts'));
	}

	public function create(PurchaseOrder $purchaseOrder): View
	{
		return view('supply.inventory_receipts.create', compact('purchaseOrder'));
	}

	public function store(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
	{
		$validated = $request->validate([
			'received_date' => ['required', 'date'],
			'reference_no' => ['nullable', 'string', 'max:255'],
			'notes' => ['nullable', 'string'],
			'items' => ['required', 'array', 'min:1'],
			'items.*.description' => ['required', 'string'],
			'items.*.unit_of_measure' => ['nullable', 'string', 'max:50'],
			'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
			'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
		]);

		$receipt = InventoryReceipt::create([
			'purchase_order_id' => $purchaseOrder->id,
			'received_date' => $validated['received_date'],
			'reference_no' => $validated['reference_no'] ?? null,
			'notes' => $validated['notes'] ?? null,
			'status' => 'posted',
			'received_by' => auth()->id(),
		]);

		foreach ($validated['items'] as $it) {
			InventoryReceiptItem::create([
				'inventory_receipt_id' => $receipt->id,
				'description' => $it['description'],
				'unit_of_measure' => $it['unit_of_measure'] ?? null,
				'quantity' => $it['quantity'],
				'unit_price' => $it['unit_price'] ?? null,
				'total_price' => isset($it['unit_price']) ? ((float)$it['unit_price'] * (float)$it['quantity']) : null,
			]);
		}

		return redirect()->route('supply.inventory-receipts.show', $receipt)->with('status', 'Inventory receipt recorded.');
	}

	public function show(InventoryReceipt $inventoryReceipt): View
	{
		$inventoryReceipt->load(['purchaseOrder', 'items']);
		return view('supply.inventory_receipts.show', ['receipt' => $inventoryReceipt]);
	}
}


