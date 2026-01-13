<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quotation extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'quotation_date' => 'date',
        'validity_date' => 'date',
        'total_amount' => 'decimal:2',
        'technical_score' => 'decimal:2',
        'financial_score' => 'decimal:2',
        'total_score' => 'decimal:2',
        'is_winning_bid' => 'boolean',
        'exceeds_abc' => 'boolean',
        'supporting_documents' => 'array',
        'awarded_at' => 'datetime',
        'evaluated_at' => 'datetime',
    ];

    public function purchaseRequest()
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    public function prItemGroup()
    {
        return $this->belongsTo(PrItemGroup::class, 'pr_item_group_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get the quotation items (line items) for this quotation
     */
    public function quotationItems()
    {
        return $this->hasMany(QuotationItem::class);
    }

    /**
     * Check if this quotation has any items exceeding ABC
     */
    public function hasItemsExceedingAbc(): bool
    {
        return $this->quotationItems()->where('is_within_abc', false)->exists();
    }

    /**
     * Get the calculated grand total from quotation items
     */
    public function getCalculatedTotal(): float
    {
        return (float) $this->quotationItems()->sum('total_price');
    }

    /**
     * Check if quotation is still within price validity period
     */
    public function isValidityExpired(): bool
    {
        return $this->validity_date < now()->toDateString();
    }

    /**
     * Check if quotation was submitted within the 4-day deadline
     */
    public function isWithinSubmissionDeadline(): bool
    {
        $rfq = $this->purchaseRequest->documents()
            ->where('document_type', 'bac_rfq')
            ->latest()
            ->first();

        if (! $rfq) {
            return true; // No RFQ, can't validate
        }

        $rfqDate = $rfq->created_at;
        $deadline = $rfqDate->copy()->addDays(4);

        return $this->quotation_date <= $deadline->toDateString();
    }

    /**
     * Check if this quotation is eligible for award
     * Based on: not exceeding ABC and within submission deadline
     */
    public function isEligibleForAward(): bool
    {
        return ! $this->exceeds_abc && $this->isWithinSubmissionDeadline();
    }
}
