<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierRegistrationController extends Controller
{
	public function create(): View
	{
		return view('suppliers.register');
	}

	public function store(Request $request): RedirectResponse
	{
		$validated = $request->validate([
			'business_name' => ['required', 'string', 'max:255'],
			'contact_person' => ['required', 'string', 'max:255'],
			'email' => ['required', 'email', 'max:255', 'unique:suppliers,email'],
			'phone' => ['required', 'string', 'max:50'],
			'address' => ['required', 'string'],
			'city' => ['required', 'string', 'max:100'],
			'province' => ['required', 'string', 'max:100'],
			'business_type' => ['required', 'in:sole_proprietorship,partnership,corporation,cooperative'],
		]);

		$supplier = Supplier::create([
			'supplier_code' => Supplier::generateSupplierCode(),
			'business_name' => $validated['business_name'],
			'contact_person' => $validated['contact_person'],
			'email' => $validated['email'],
			'phone' => $validated['phone'],
			'address' => $validated['address'],
			'city' => $validated['city'],
			'province' => $validated['province'],
			'business_type' => $validated['business_type'],
			'password' => null,
			'status' => 'pending_verification',
		]);

		return redirect()->route('suppliers.register')->with('status', 'Registration received. We will contact you for verification.');
	}
}


