<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class PurchaseRequest extends Model
{
    use HasFactory;

    /**
     * Guard no attributes; we'll validate at controller level.
     */
    protected $guarded = [];

    protected $casts = [
        'date_needed' => 'date',
        'earmark_date_to' => 'date',
        'earmark_object_expenditures' => 'array',
        'estimated_total' => 'decimal:2',
        'status_updated_at' => 'datetime',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'completed_at' => 'datetime',
        'returned_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function currentHandler()
    {
        return $this->belongsTo(User::class, 'current_handler_id');
    }

    public function returnedBy()
    {
        return $this->belongsTo(User::class, 'returned_by');
    }

    public function replacesPr()
    {
        return $this->belongsTo(PurchaseRequest::class, 'replaces_pr_id');
    }

    public function replacedByPr()
    {
        return $this->belongsTo(PurchaseRequest::class, 'replaced_by_pr_id');
    }

    public function items()
    {
        return $this->hasMany(PurchaseRequestItem::class);
    }

    public function itemGroups()
    {
        return $this->hasMany(PrItemGroup::class, 'purchase_request_id');
    }

    public function documents()
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function workflowApprovals()
    {
        return $this->hasMany(WorkflowApproval::class);
    }

    public function resolutionSignatories()
    {
        return $this->hasMany(ResolutionSignatory::class);
    }

    public function rfqSignatories()
    {
        return $this->hasMany(RfqSignatory::class);
    }

    public function quotations()
    {
        return $this->hasMany(Quotation::class);
    }

    public function aoqGenerations()
    {
        return $this->hasMany(AoqGeneration::class);
    }

    public function aoqItemDecisions()
    {
        return $this->hasMany(AoqItemDecision::class);
    }

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function activities()
    {
        return $this->hasMany(PurchaseRequestActivity::class)->orderByDesc('created_at');
    }

    /**
     * Scope a query to exclude archived PRs.
     */
    public function scopeNotArchived($query)
    {
        return $query->where('is_archived', false);
    }

    /**
     * Scope a query to PRs for a specific college.
     */
    public function scopeForCollege($query, int $collegeId)
    {
        return $query->where('department_id', $collegeId);
    }

    /**
     * Scope a query to returned PRs.
     */
    public function scopeReturned($query)
    {
        return $query->where('status', 'returned_by_supply');
    }

    /**
     * Scope a query to active (non-returned, non-rejected, non-cancelled) PRs.
     */
    public function scopeActiveStatus($query)
    {
        return $query->whereNotIn('status', ['returned_by_supply', 'rejected', 'cancelled']);
    }

    /**
     * Generate next PR number in format: PR-MMYY-####
     * Example: PR-0126-0003 (January 2026)
     */
    public static function generateNextPrNumber(?Carbon $asOf = null): string
    {
        $asOf = $asOf ?: now();
        $monthYear = $asOf->format('my'); // MMYY format
        $prefix = 'PR-'.$monthYear.'-';
        $last = static::where('pr_number', 'like', $prefix.'%')
            ->orderByDesc('pr_number')
            ->value('pr_number');

        $nextSequence = 1;
        if ($last) {
            $parts = explode('-', $last);
            $seqStr = end($parts);
            $nextSequence = intval($seqStr) + 1;
        }

        return $prefix.str_pad((string) $nextSequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Calculate total cost of all items in this PR
     */
    public function calculateTotalCost(): float
    {
        return (float) $this->items->sum(function ($item) {
            return $item->quantity_requested * $item->estimated_unit_cost;
        });
    }

    /**
     * Reserve budget for this PR
     */
    public function reserveDepartmentBudget(): bool
    {
        $fiscalYear = $this->created_at->year;
        $budget = DepartmentBudget::getOrCreateForDepartment($this->department_id, $fiscalYear);

        $totalCost = $this->calculateTotalCost();

        return $budget->reserveBudget($totalCost);
    }

    /**
     * Utilize budget when PR is completed
     */
    public function utilizeDepartmentBudget(): bool
    {
        $fiscalYear = $this->created_at->year;
        $budget = DepartmentBudget::getOrCreateForDepartment($this->department_id, $fiscalYear);

        $totalCost = $this->calculateTotalCost();

        return $budget->utilizeBudget($totalCost);
    }

    /**
     * Release reserved budget when PR is cancelled/rejected
     */
    public function releaseReservedBudget(): bool
    {
        $fiscalYear = $this->created_at->year;
        $budget = DepartmentBudget::getOrCreateForDepartment($this->department_id, $fiscalYear);

        $totalCost = $this->calculateTotalCost();

        return $budget->releaseReservedBudget($totalCost);
    }

    /**
     * Generate next earmark ID in format: EM-MMYY-####
     * Example: EM-0126-0042 (January 2026)
     */
    public static function generateNextEarmarkId(?Carbon $asOf = null): string
    {
        $asOf = $asOf ?: now();
        $monthYear = $asOf->format('my'); // MMYY format
        $prefix = 'EM-'.$monthYear.'-';

        $last = static::where('earmark_id', 'like', $prefix.'%')
            ->orderByDesc('earmark_id')
            ->value('earmark_id');

        $nextSequence = 1;
        if ($last) {
            $parts = explode('-', $last);
            $seqStr = end($parts);
            $nextSequence = intval($seqStr) + 1;
        }

        return $prefix.str_pad((string) $nextSequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Generate next resolution number in format: RES-MMYY-####
     * Example: RES-0126-0001 (January 2026)
     */
    public static function generateNextResolutionNumber(?Carbon $asOf = null): string
    {
        $asOf = $asOf ?: now();
        $monthYear = $asOf->format('my'); // MMYY format
        $prefix = 'RES-'.$monthYear.'-';

        $last = static::where('resolution_number', 'like', $prefix.'%')
            ->orderByDesc('resolution_number')
            ->value('resolution_number');

        $nextSequence = 1;
        if ($last) {
            $parts = explode('-', $last);
            $seqStr = end($parts);
            $nextSequence = intval($seqStr) + 1;
        }

        return $prefix.str_pad((string) $nextSequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Generate next RFQ number in format: RFQ-MMYY-####
     * Example: RFQ-0126-0001 (January 2026)
     */
    public static function generateNextRfqNumber(?Carbon $asOf = null): string
    {
        $asOf = $asOf ?: now();
        $monthYear = $asOf->format('my'); // MMYY format
        $prefix = 'RFQ-'.$monthYear.'-';

        $last = static::where('rfq_number', 'like', $prefix.'%')
            ->orderByDesc('rfq_number')
            ->value('rfq_number');

        $nextSequence = 1;
        if ($last) {
            $parts = explode('-', $last);
            $seqStr = end($parts);
            $nextSequence = intval($seqStr) + 1;
        }

        return $prefix.str_pad((string) $nextSequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Check if all item groups have at least one PO created.
     * Used to determine if PR should be in 'partial_po_generation' or 'po_generation' status.
     */
    public function allGroupsHavePo(): bool
    {
        $groups = $this->itemGroups;

        // If no groups, check if any PO exists for the PR
        if ($groups->isEmpty()) {
            return $this->purchaseOrders()->exists();
        }

        // All groups must have at least one PO
        return $groups->every(fn ($group) => $group->hasExistingPo());
    }

    /**
     * Check if this PR has been through BAC evaluation.
     * Used to determine if BAC should have view access.
     */
    public function hasBeenThroughBac(): bool
    {
        return in_array($this->status, [
            'bac_evaluation',
            'bac_approved',
            'partial_po_generation',
            'po_generation',
            'po_approved',
            'supplier_processing',
            'delivered',
            'completed',
        ]);
    }

    /**
     * Compute this PR's status from the aggregate of its group statuses.
     * Returns null for non-grouped PRs so callers can fall back to stored status.
     *
     * The PR status reflects the "earliest" (minimum) group status so that
     * operations are allowed as long as any group still needs them.
     *
     * @return string|null Computed PR status, or null if no groups exist
     */
    public function computeStatusFromGroups(): ?string
    {
        $groups = $this->itemGroups;

        if ($groups->isEmpty()) {
            return null;
        }

        /** @var array<string, string> Maps group status → PR status */
        $statusMap = [
            'pending' => 'bac_evaluation',
            'aoq_generated' => 'bac_approved',
            'po_created' => 'partial_po_generation',
            'all_po_approved' => 'po_approved',
            'processing' => 'supplier_processing',
            'delivered' => 'delivered',
            'completed' => 'completed',
        ];

        $orderedKeys = array_keys($statusMap);

        $groupStatuses = $groups->map(fn ($g) => $g->computeStatus());

        // Use the minimum (earliest) group status as the effective PR status
        $minIndex = $groupStatuses
            ->map(fn ($s) => array_search($s, $orderedKeys, true))
            ->min();

        $minGroupStatus = $orderedKeys[$minIndex] ?? 'pending';

        return $statusMap[$minGroupStatus] ?? $this->status;
    }

    /**
     * Get the effective PR status, using computed group status for grouped PRs
     * and the stored status for non-grouped PRs.
     */
    public function getEffectiveStatus(): string
    {
        return $this->computeStatusFromGroups() ?? $this->status;
    }

    /**
     * Sync the stored PR status with the computed group status.
     * Call this after any PO status change to keep the stored status accurate
     * for database queries and reports.
     */
    public function syncStatusFromGroups(): void
    {
        $computed = $this->computeStatusFromGroups();

        if ($computed === null || $this->status === $computed) {
            return;
        }

        $this->status = $computed;
        $this->status_updated_at = now();

        if ($computed === 'completed') {
            $this->completed_at = now();
        }

        $this->save();
    }

    /**
     * Determine whether any group (or the non-grouped PR itself) is still in a
     * state that allows AOQ generation.
     */
    public function canCreateAoq(): bool
    {
        $groups = $this->itemGroups;

        if ($groups->isNotEmpty()) {
            return $groups->some(fn ($g) => $g->canCreateAoq());
        }

        return in_array($this->status, ['bac_evaluation']);
    }

    /**
     * Determine whether any group (or the non-grouped PR itself) is still in a
     * state that allows PO creation.
     */
    public function canCreatePo(): bool
    {
        $groups = $this->itemGroups;

        if ($groups->isNotEmpty()) {
            return $groups->some(fn ($g) => $g->canCreatePo());
        }

        return in_array($this->status, ['bac_approved', 'bac_evaluation']);
    }

    /**
     * Determine whether groups (or the non-grouped PR itself) can still be
     * managed (created, edited, or deleted).
     */
    public function canManageGroups(): bool
    {
        return in_array($this->getEffectiveStatus(), ['bac_evaluation', 'partial_po_generation']);
    }
}
