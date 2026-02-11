<?php

namespace App\Services;

use App\Models\PrItemGroup;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseRequest;
use App\Models\QuotationItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PurchaseOrderService
{
    /**
     * Get winning items grouped by supplier for a purchase request or group
     */
    public function getWinningItemsGroupedBySupplier(
        PurchaseRequest $purchaseRequest,
        ?PrItemGroup $itemGroup = null
    ): Collection {
        // Get all winning quotation items for this PR/group
        $query = QuotationItem::with([
            'quotation.supplier',
            'purchaseRequestItem',
        ])
            ->where('is_winner', true)
            ->where('is_withdrawn', false)
            ->whereHas('quotation', function ($q) use ($purchaseRequest, $itemGroup) {
                $q->where('purchase_request_id', $purchaseRequest->id);
                if ($itemGroup) {
                    $q->where('pr_item_group_id', $itemGroup->id);
                }
            });

        $winningItems = $query->get();

        // Group by supplier
        $groupedBySupplier = $winningItems->groupBy(function ($item) {
            return $item->quotation->supplier_id;
        });

        // Transform into a more usable structure
        return $groupedBySupplier->map(function ($items, $supplierId) {
            $firstItem = $items->first();
            $supplier = $firstItem->quotation->supplier;
            $quotation = $firstItem->quotation;

            $itemsData = $items->map(function ($quotationItem) {
                return [
                    'quotation_item' => $quotationItem,
                    'pr_item' => $quotationItem->purchaseRequestItem,
                    'quantity' => $quotationItem->purchaseRequestItem->quantity_requested,
                    'unit_price' => $quotationItem->unit_price,
                    'total_price' => $quotationItem->total_price,
                ];
            });

            $total = $itemsData->sum('total_price');

            return [
                'supplier' => $supplier,
                'quotation' => $quotation,
                'items' => $itemsData,
                'total_amount' => $total,
                'item_count' => $items->count(),
            ];
        })->values();
    }

    /**
     * Create multiple purchase orders in a batch
     */
    public function createBatchPurchaseOrders(
        PurchaseRequest $purchaseRequest,
        ?PrItemGroup $itemGroup,
        array $poDataArray
    ): Collection {
        return DB::transaction(function () use ($purchaseRequest, $itemGroup, $poDataArray) {
            $createdPOs = collect();

            foreach ($poDataArray as $poData) {
                // Create the purchase order
                $po = PurchaseOrder::create([
                    'po_number' => PurchaseOrder::generateNextPoNumber(),
                    'purchase_request_id' => $purchaseRequest->id,
                    'pr_item_group_id' => $itemGroup?->id,
                    'supplier_id' => $poData['supplier_id'],
                    'quotation_id' => $poData['quotation_id'],
                    'po_date' => now(),
                    'tin' => $poData['tin'] ?? null,
                    'supplier_name_override' => $poData['supplier_name_override'] ?? null,
                    'funds_cluster' => $poData['funds_cluster'],
                    'funds_available' => $poData['funds_available'],
                    'ors_burs_no' => $poData['ors_burs_no'],
                    'ors_burs_date' => $poData['ors_burs_date'],
                    'total_amount' => $poData['total_amount'],
                    'delivery_address' => $poData['delivery_address'],
                    'delivery_date_required' => $poData['delivery_date_required'],
                    'terms_and_conditions' => $poData['terms_and_conditions'],
                    'special_instructions' => $poData['special_instructions'] ?? null,
                    'status' => 'pending_approval',
                ]);

                // Create purchase order items
                foreach ($poData['items'] as $itemData) {
                    PurchaseOrderItem::create([
                        'purchase_order_id' => $po->id,
                        'purchase_request_item_id' => $itemData['purchase_request_item_id'],
                        'quotation_item_id' => $itemData['quotation_item_id'],
                        'quantity' => $itemData['quantity'],
                        'unit_price' => $itemData['unit_price'],
                        'total_price' => $itemData['total_price'],
                    ]);
                }

                $createdPOs->push($po);
            }

            // Update PR status to po_generation
            $purchaseRequest->status = 'po_generation';
            $purchaseRequest->status_updated_at = now();
            $purchaseRequest->save();

            return $createdPOs;
        });
    }

    /**
     * Check if there are multiple winning suppliers for a PR/group
     */
    public function hasMultipleWinningSuppliers(
        PurchaseRequest $purchaseRequest,
        ?PrItemGroup $itemGroup = null
    ): bool {
        $winningItemsBySupplier = $this->getWinningItemsGroupedBySupplier($purchaseRequest, $itemGroup);

        return $winningItemsBySupplier->count() > 1;
    }
}
