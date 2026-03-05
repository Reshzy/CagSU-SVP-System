<?php

namespace App\Http\Requests;

use App\Models\PpmpItem;
use App\Models\PurchaseRequest;
use App\Services\PpmpQuarterlyTracker;
use Illuminate\Support\Facades\Auth;

class StoreReplacementPurchaseRequestRequest extends StorePurchaseRequestRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // First check parent authorization (user must have department)
        if (! parent::authorize()) {
            return false;
        }

        // Get the original PR from route parameter
        $originalPr = $this->route('originalPr');

        // Ensure the PR is returned and belongs to the current user
        if (! $originalPr instanceof PurchaseRequest) {
            return false;
        }

        if ($originalPr->status !== 'returned_by_supply') {
            return false;
        }

        if ($originalPr->requester_id !== Auth::id()) {
            return false;
        }

        return true;
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return array_merge(parent::messages(), [
            // Add any replacement-specific messages here
        ]);
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $originalPr = $this->route('originalPr');

            if ($originalPr && $originalPr->status !== 'returned_by_supply') {
                $validator->errors()->add('originalPr', 'Can only create replacement for returned purchase requests.');
            }

            if ($originalPr && $originalPr->requester_id !== Auth::id()) {
                $validator->errors()->add('originalPr', 'Unauthorized to create replacement for this purchase request.');
            }
        });
    }

    /**
     * Override: Validate PPMP item with grace period support for replacement PRs
     */
    protected function validatePpmpItemQuarter($ppmpItemId, string $attribute, $fail): void
    {
        $ppmpItem = PpmpItem::with(['ppmp', 'appItem'])->find($ppmpItemId);

        if (! $ppmpItem) {
            $fail('The selected PPMP item does not exist.');

            return;
        }

        // Check if PPMP is validated
        if ($ppmpItem->ppmp->status !== 'validated') {
            $fail("The PPMP for item '{$ppmpItem->appItem->item_name}' must be validated before creating a PR.");

            return;
        }

        $quarterlyTracker = app(PpmpQuarterlyTracker::class);
        $currentQuarter = $quarterlyTracker->getQuarterFromDate();
        $availableQuarters = $quarterlyTracker->getAvailableQuartersForReplacement();

        // Check if item has quantity allocated for any available quarter (current or grace period),
        // allowing the replacement PR to re-use the original returned PR's reserved quantity.
        $originalPr = $this->route('originalPr');

        $itemAvailable = false;
        $availableInQuarter = null;

        foreach ($availableQuarters as $quarter) {
            if (! $ppmpItem->hasQuantityForQuarter($quarter)) {
                continue;
            }

            // Remaining for all PRs except the original returned PR
            $remainingExcludingOriginal = $ppmpItem->getRemainingQuantity($quarter, $originalPr?->id);

            // Quantity already reserved in the original returned PR for this PPMP item and quarter
            $originalQtyForQuarter = 0;
            if ($originalPr instanceof PurchaseRequest) {
                $originalQtyForQuarter = $originalPr->items()
                    ->where('ppmp_item_id', $ppmpItem->id)
                    ->where('ppmp_quarter', $quarter)
                    ->sum('quantity_requested');
            }

            $effectiveAvailable = $remainingExcludingOriginal + $originalQtyForQuarter;

            if ($effectiveAvailable > 0) {
                $itemAvailable = true;
                $availableInQuarter = $quarter;
                break;
            }
        }

        if (! $itemAvailable) {
            $quarterLabel = $quarterlyTracker->getQuarterLabel($currentQuarter);
            $gracePeriodInfo = $quarterlyTracker->getGracePeriodInfo();

            if ($gracePeriodInfo && $gracePeriodInfo['active']) {
                $previousQuarterLabel = $gracePeriodInfo['quarter_label'];
                $fail("Item '{$ppmpItem->appItem->item_name}' is not allocated for the current quarter (Q{$currentQuarter} - {$quarterLabel}) or the previous quarter (Q{$gracePeriodInfo['quarter']} - {$previousQuarterLabel}, grace period until {$gracePeriodInfo['end_date_formatted']}).");
            } else {
                $nextQuarter = $ppmpItem->getNextAvailableQuarter();
                if ($nextQuarter) {
                    $nextQuarterLabel = $quarterlyTracker->getQuarterLabel($nextQuarter);
                    $fail("Item '{$ppmpItem->appItem->item_name}' is not allocated for the current quarter (Q{$currentQuarter} - {$quarterLabel}). This item is available in Q{$nextQuarter} ({$nextQuarterLabel}).");
                } else {
                    $fail("Item '{$ppmpItem->appItem->item_name}' is not allocated for the current quarter (Q{$currentQuarter} - {$quarterLabel}).");
                }
            }
        }
    }

    /**
     * Override: Validate quantity with grace period support for replacement PRs
     */
    protected function validateQuantityAgainstPpmpQuarter(string $attribute, $quantity, $fail): void
    {
        // Extract the item index from attribute (e.g., "items.0.quantity_requested" -> 0)
        preg_match('/items\.(\d+)\.quantity_requested/', $attribute, $matches);
        if (! isset($matches[1])) {
            return;
        }

        $itemIndex = $matches[1];
        $items = $this->input('items', []);

        if (! isset($items[$itemIndex]['ppmp_item_id'])) {
            return; // Skip validation for custom items
        }

        $ppmpItemId = $items[$itemIndex]['ppmp_item_id'];
        $ppmpItem = PpmpItem::with('appItem')->find($ppmpItemId);

        if (! $ppmpItem) {
            return;
        }

        $quarterlyTracker = app(PpmpQuarterlyTracker::class);
        $availableQuarters = $quarterlyTracker->getAvailableQuartersForReplacement();

        // Remaining quantity across available quarters (current + grace period if applicable),
        // allowing the replacement PR to re-use the original returned PR's reserved quantity.
        $originalPr = $this->route('originalPr');

        $totalRemainingExcludingOriginal = 0;
        $originalUsageAcrossQuarters = 0;

        foreach ($availableQuarters as $quarter) {
            $remainingInQuarter = $ppmpItem->getRemainingQuantity($quarter, $originalPr?->id);
            if ($remainingInQuarter > 0) {
                $totalRemainingExcludingOriginal += $remainingInQuarter;
            }

            if ($originalPr instanceof PurchaseRequest) {
                $originalUsageAcrossQuarters += $originalPr->items()
                    ->where('ppmp_item_id', $ppmpItem->id)
                    ->where('ppmp_quarter', $quarter)
                    ->sum('quantity_requested');
            }
        }

        $totalAllowedQty = $totalRemainingExcludingOriginal + $originalUsageAcrossQuarters;

        if ($quantity > $totalAllowedQty) {
            $currentQuarter = $quarterlyTracker->getQuarterFromDate();
            $quarterLabel = $quarterlyTracker->getQuarterLabel($currentQuarter);
            $gracePeriodInfo = $quarterlyTracker->getGracePeriodInfo();

            if ($gracePeriodInfo && $gracePeriodInfo['active']) {
                $fail("Requested quantity ({$quantity}) for '{$ppmpItem->appItem->item_name}' exceeds remaining quantity ({$totalAllowedQty}) across current quarter and grace period (including the original returned PR's reserved quantity).");
            } else {
                $fail("Requested quantity ({$quantity}) for '{$ppmpItem->appItem->item_name}' exceeds remaining quantity ({$totalAllowedQty}) for Q{$currentQuarter} ({$quarterLabel}) (including the original returned PR's reserved quantity).");
            }
        }
    }
}
