<?php

namespace App\Http\Controllers;

use App\Models\PurchaseRequest;
use App\Models\Quotation;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BacQuotationController extends Controller
{
    public function index(Request $request): View
    {
        $requests = PurchaseRequest::withCount('items')
            ->where('status', 'bac_evaluation')
            ->latest()
            ->paginate(15);

        return view('bac.quotations.index', compact('requests'));
    }

    public function manage(PurchaseRequest $purchaseRequest): View
    {
        abort_unless($purchaseRequest->status === 'bac_evaluation', 403);
        $purchaseRequest->load(['items']);
        $suppliers = Supplier::where('status', 'active')->orderBy('business_name')->get();
        $quotations = Quotation::where('purchase_request_id', $purchaseRequest->id)->with('supplier')->get();
        return view('bac.quotations.manage', compact('purchaseRequest', 'suppliers', 'quotations'));
    }

    public function store(Request $request, PurchaseRequest $purchaseRequest): RedirectResponse
    {
        $validated = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'quotation_date' => ['required', 'date'],
            'validity_date' => ['required', 'date', 'after_or_equal:quotation_date'],
            'total_amount' => ['required', 'numeric', 'min:0'],
        ]);

        $quotationNumber = self::generateQuotationNumber();

        Quotation::create([
            'quotation_number' => $quotationNumber,
            'purchase_request_id' => $purchaseRequest->id,
            'supplier_id' => $validated['supplier_id'],
            'quotation_date' => $validated['quotation_date'],
            'validity_date' => $validated['validity_date'],
            'total_amount' => $validated['total_amount'],
            'bac_status' => 'pending_evaluation',
        ]);

        return back()->with('status', 'Quotation recorded.');
    }

    public function evaluate(Request $request, Quotation $quotation): RedirectResponse
    {
        $validated = $request->validate([
            'technical_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'financial_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'bac_status' => ['required', 'in:compliant,non_compliant,lowest_bidder'],
            'bac_remarks' => ['nullable', 'string'],
        ]);

        $total = null;
        if (isset($validated['technical_score']) && isset($validated['financial_score'])) {
            $total = round(($validated['technical_score'] * 0.6) + ($validated['financial_score'] * 0.4), 2);
        }

        $quotation->update([
            'technical_score' => $validated['technical_score'] ?? null,
            'financial_score' => $validated['financial_score'] ?? null,
            'total_score' => $total,
            'bac_status' => $validated['bac_status'],
            'bac_remarks' => $validated['bac_remarks'] ?? null,
            'evaluated_at' => now(),
        ]);

        return back()->with('status', 'Quotation evaluated.');
    }

    public function finalize(Request $request, PurchaseRequest $purchaseRequest): RedirectResponse
    {
        abort_unless($purchaseRequest->status === 'bac_evaluation', 403);

        // Mark winning quotation
        $winnerId = $request->integer('winning_quotation_id');
        if ($winnerId) {
            Quotation::where('purchase_request_id', $purchaseRequest->id)->update(['is_winning_bid' => false]);
            Quotation::where('id', $winnerId)->update(['is_winning_bid' => true, 'bac_status' => 'awarded', 'awarded_at' => now()]);
        }

        // Move PR status forward to BAC approved (abstract ready)
        $purchaseRequest->status = 'bac_approved';
        $purchaseRequest->status_updated_at = now();
        $purchaseRequest->save();

        return redirect()->route('bac.quotations.index')->with('status', 'Abstract finalized.');
    }

    protected static function generateQuotationNumber(): string
    {
        $year = now()->year;
        $prefix = 'QUO-' . $year . '-';
        $last = Quotation::where('quotation_number', 'like', $prefix . '%')->orderByDesc('quotation_number')->value('quotation_number');
        $next = 1;
        if ($last) {
            $parts = explode('-', $last);
            $next = intval(end($parts)) + 1;
        }
        return $prefix . str_pad((string)$next, 4, '0', STR_PAD_LEFT);
    }
}


