<?php

namespace App\Livewire\Supply;

use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class PurchaseOrderTable extends Component
{
    use WithPagination;

    #[Url(as: 'po')]
    public string $poNumberSearch = '';

    #[Url(as: 'supplier')]
    public string $supplierFilter = '';

    #[Url(as: 'pr')]
    public string $prNumberFilter = '';

    #[Url]
    public string $statusFilter = '';

    public function updatingPoNumberSearch(): void
    {
        $this->resetPage();
    }

    public function updatingSupplierFilter(): void
    {
        $this->resetPage();
    }

    public function updatingPrNumberFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function render(): \Illuminate\View\View
    {
        $query = PurchaseOrder::query()
            ->with(['purchaseRequest', 'supplier'])
            ->latest('po_date');

        if ($this->poNumberSearch) {
            $query->where('po_number', 'like', "%{$this->poNumberSearch}%");
        }

        if ($this->supplierFilter) {
            $query->where('supplier_id', $this->supplierFilter);
        }

        if ($this->prNumberFilter) {
            $query->whereHas('purchaseRequest', fn ($q) => $q->where('pr_number', $this->prNumberFilter));
        }

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        $orders = $query->paginate(15);

        $statuses = [
            'draft' => 'Draft',
            'pending_approval' => 'Pending Approval',
            'approved' => 'Approved',
            'sent_to_supplier' => 'Sent to Supplier',
            'acknowledged_by_supplier' => 'Acknowledged by Supplier',
            'in_progress' => 'In Progress',
            'delivered' => 'Delivered',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
        ];

        $suppliers = Supplier::query()
            ->whereHas('purchaseOrders')
            ->orderBy('business_name')
            ->get(['id', 'business_name']);

        $prNumbers = PurchaseOrder::query()
            ->with('purchaseRequest:id,pr_number')
            ->get()
            ->pluck('purchaseRequest.pr_number')
            ->filter()
            ->unique()
            ->sort()
            ->values();

        return view('livewire.supply.purchase-order-table', [
            'orders' => $orders,
            'statuses' => $statuses,
            'suppliers' => $suppliers,
            'prNumbers' => $prNumbers,
        ]);
    }
}
