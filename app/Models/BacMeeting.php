<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BacMeeting extends Model
{
	use HasFactory;

	protected $guarded = [];

	protected $casts = [
		'meeting_datetime' => 'datetime',
	];

	public function purchaseRequest()
	{
		return $this->belongsTo(PurchaseRequest::class);
	}

	public function attendees()
	{
		return $this->belongsToMany(User::class, 'bac_meeting_attendees')
			->withPivot(['role_at_meeting', 'attended', 'remarks'])
			->withTimestamps();
	}

	public function creator()
	{
		return $this->belongsTo(User::class, 'created_by');
	}
}


