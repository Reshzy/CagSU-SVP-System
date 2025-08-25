<?php

namespace App\Http\Controllers;

use App\Models\Quotation;
use App\Models\Supplier;
use App\Models\PurchaseRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierQuotationPublicController extends Controller
{
    public function create(): View
    {
        return view('suppliers.quotation_submit');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'pr_number' => ['required', 'string', 'exists:purchase_requests,pr_number'],
            'supplier_email' => ['required', 'email', 'exists:suppliers,email'],
            'quotation_date' => ['required', 'date'],
            'validity_date' => ['required', 'date', 'after_or_equal:quotation_date'],
            'total_amount' => ['required', 'numeric', 'min:0'],
        ]);

        $pr = PurchaseRequest::where('pr_number', $validated['pr_number'])->firstOrFail();
        $supplier = Supplier::where('email', $validated['supplier_email'])->firstOrFail();

        $number = $this->generateQuotationNumber();

        Quotation::create([
            'quotation_number' => $number,
            'purchase_request_id' => $pr->id,
            'supplier_id' => $supplier->id,
            'quotation_date' => $validated['quotation_date'],
            'validity_date' => $validated['validity_date'],
            'total_amount' => $validated['total_amount'],
            'bac_status' => 'pending_evaluation',
        ]);

        return redirect()->route('suppliers.quotations.submit')->with('status', 'Quotation submitted successfully.');
    }

    protected function generateQuotationNumber(): string
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


