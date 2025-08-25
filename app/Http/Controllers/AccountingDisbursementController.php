<?php

namespace App\Http\Controllers;

use App\Models\DisbursementVoucher;
use App\Models\PurchaseOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class AccountingDisbursementController extends Controller
{
	public function index(Request $request): View
	{
		$vouchers = DisbursementVoucher::with(['purchaseOrder', 'supplier'])
			->latest('voucher_date')
			->paginate(15);
		return view('accounting.vouchers.index', compact('vouchers'));
	}

	public function create(PurchaseOrder $purchaseOrder): View
	{
		abort_unless(in_array($purchaseOrder->status, ['delivered', 'completed']), 403);
		return view('accounting.vouchers.create', compact('purchaseOrder'));
	}

	public function store(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
	{
		abort_unless(in_array($purchaseOrder->status, ['delivered', 'completed']), 403);
		$validated = $request->validate([
			'amount' => ['required', 'numeric', 'min:0'],
			'voucher_date' => ['required', 'date'],
			'remarks' => ['nullable', 'string'],
		]);

		$voucher = DisbursementVoucher::create([
			'voucher_number' => DisbursementVoucher::generateNextVoucherNumber(),
			'purchase_order_id' => $purchaseOrder->id,
			'supplier_id' => $purchaseOrder->supplier_id,
			'amount' => $validated['amount'],
			'voucher_date' => $validated['voucher_date'],
			'status' => 'submitted',
			'prepared_by' => Auth::id(),
			'remarks' => $validated['remarks'] ?? null,
		]);

		return redirect()->route('accounting.vouchers.show', $voucher)->with('status', 'Disbursement voucher created.');
	}

	public function show(DisbursementVoucher $voucher): View
	{
		$voucher->load(['purchaseOrder', 'supplier', 'preparedBy']);
		return view('accounting.vouchers.show', compact('voucher'));
	}

	public function update(Request $request, DisbursementVoucher $voucher): RedirectResponse
	{
		$validated = $request->validate([
			'action' => ['required', 'in:approve,release,mark_paid,cancel'],
			'remarks' => ['nullable', 'string'],
		]);

		switch ($validated['action']) {
			case 'approve':
				$voucher->status = 'approved';
				$voucher->approved_by = Auth::id();
				$voucher->approved_at = now();
				break;
			case 'release':
				$voucher->status = 'released';
				$voucher->released_at = now();
				break;
			case 'mark_paid':
				$voucher->status = 'paid';
				$voucher->paid_at = now();
				break;
			case 'cancel':
				$voucher->status = 'cancelled';
				break;
		}

		if (!empty($validated['remarks'])) {
			$voucher->remarks = trim((string)$validated['remarks']);
		}
		$voucher->save();

		return back()->with('status', 'Voucher updated.');
	}
}


