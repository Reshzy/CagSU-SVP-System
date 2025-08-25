<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
	use HasFactory;

	protected $guarded = [];

	protected $casts = [
		'po_date' => 'date',
		'total_amount' => 'decimal:2',
		'delivery_date_required' => 'date',
		'approved_at' => 'datetime',
		'sent_to_supplier_at' => 'datetime',
		'acknowledged_at' => 'datetime',
		'actual_delivery_date' => 'date',
		'delivery_complete' => 'boolean',
	];

	public function purchaseRequest()
	{
		return $this->belongsTo(PurchaseRequest::class);
	}

	public function supplier()
	{
		return $this->belongsTo(Supplier::class);
	}

	public function quotation()
	{
		return $this->belongsTo(Quotation::class);
	}

	public static function generateNextPoNumber(): string
	{
		$year = now()->year;
		$prefix = 'PO-' . $year . '-';
		$last = static::where('po_number', 'like', $prefix . '%')
			->orderByDesc('po_number')
			->value('po_number');
		$next = 1;
		if ($last) {
			$parts = explode('-', $last);
			$next = intval(end($parts)) + 1;
		}
		return $prefix . str_pad((string)$next, 4, '0', STR_PAD_LEFT);
	}
}


