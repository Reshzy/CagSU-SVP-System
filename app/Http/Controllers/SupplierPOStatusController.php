<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierPOStatusController extends Controller
{
    public function show(Request $request): View
    {
        $email = $request->string('supplier_email')->toString();

        $supplier = null;
        $orders = collect();
        if ($email) {
            $supplier = Supplier::where('email', $email)->first();
            if ($supplier) {
                $orders = PurchaseOrder::with(['purchaseRequest'])
                    ->where('supplier_id', $supplier->id)
                    ->latest('po_date')
                    ->paginate(15);
            }
        }

        return view('suppliers.po_status', compact('supplier', 'orders', 'email'));
    }
}


