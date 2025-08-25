<?php

namespace App\Http\Controllers;

use App\Models\BacMeeting;
use App\Models\PurchaseRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class BacMeetingController extends Controller
{
	public function index(Request $request): View
	{
		$meetings = BacMeeting::with(['purchaseRequest', 'creator'])
			->latest('meeting_datetime')
			->paginate(15);

		return view('bac.meetings.index', compact('meetings'));
	}

	public function create(Request $request): View
	{
		$purchaseRequests = PurchaseRequest::whereIn('status', ['ceo_approval', 'bac_evaluation', 'bac_approved'])
			->orderByDesc('created_at')
			->limit(100)
			->get();
		return view('bac.meetings.create', compact('purchaseRequests'));
	}

	public function store(Request $request): RedirectResponse
	{
		$validated = $request->validate([
			'purchase_request_id' => ['nullable', 'exists:purchase_requests,id'],
			'meeting_datetime' => ['required', 'date'],
			'location' => ['nullable', 'string', 'max:255'],
			'title' => ['nullable', 'string', 'max:255'],
			'agenda' => ['nullable', 'string'],
		]);

		$meeting = BacMeeting::create([
			'purchase_request_id' => $validated['purchase_request_id'] ?? null,
			'meeting_datetime' => $validated['meeting_datetime'],
			'location' => $validated['location'] ?? null,
			'title' => $validated['title'] ?? null,
			'agenda' => $validated['agenda'] ?? null,
			'created_by' => Auth::id(),
		]);

		return redirect()->route('bac.meetings.show', $meeting)->with('status', 'BAC meeting scheduled.');
	}

	public function show(BacMeeting $meeting): View
	{
		$meeting->load(['purchaseRequest', 'attendees', 'creator']);
		return view('bac.meetings.show', compact('meeting'));
	}
}


