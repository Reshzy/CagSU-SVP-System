<?php

namespace App\Livewire\Supply;

use App\Models\PurchaseOrder;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class PurchaseOrderTable extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $statusFilter = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = PurchaseOrder::query()
            ->with(['purchaseRequest', 'supplier'])
            ->latest('po_date');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('po_number', 'like', "%{$this->search}%")
                    ->orWhereHas('purchaseRequest', function ($q) {
                        $q->where('pr_number', 'like', "%{$this->search}%");
                    })
                    ->orWhereHas('supplier', function ($q) {
                        $q->where('business_name', 'like', "%{$this->search}%");
                    });
            });
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

        return view('livewire.supply.purchase-order-table', [
            'orders' => $orders,
            'statuses' => $statuses,
        ]);
    }
}
