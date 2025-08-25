@section('title', 'Suppliers')

<x-app-layout>
	<x-slot name="header">
		<div class="flex items-center justify-between">
			<h2 class="font-semibold text-2xl text-gray-800 leading-tight">{{ __('Suppliers') }}</h2>
			<div class="flex items-center gap-2">
				@can('manage-suppliers')
					<a href="{{ route('supply.suppliers.create') }}" class="px-3 py-2 bg-cagsu-maroon text-white rounded-md">New Supplier</a>
				@endcan
				<a href="{{ route('reports.suppliers') }}" class="px-3 py-2 bg-gray-100 text-gray-700 rounded-md">Suppliers Report</a>
			</div>
		</div>
	</x-slot>

	<div class="py-8">
		<div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
			<div class="bg-white shadow rounded-lg">
				<div class="p-4 border-b flex items-center justify-between">
					<form method="GET" class="flex items-center gap-2">
						<input type="text" name="q" value="{{ request('q') }}" placeholder="Search suppliers..." class="border-gray-300 rounded-md" />
						<button class="px-3 py-2 bg-gray-100 rounded-md">Search</button>
					</form>
				</div>
				<div class="p-4 overflow-x-auto">
					<table class="min-w-full text-sm">
						<thead>
							<tr class="text-left text-gray-500">
								<th class="py-2 pr-4">Code</th>
								<th class="py-2 pr-4">Business Name</th>
								<th class="py-2 pr-4">Contact</th>
								<th class="py-2 pr-4">Email</th>
								<th class="py-2 pr-4">Status</th>
								<th class="py-2 pr-4"></th>
							</tr>
						</thead>
						<tbody>
							@forelse($suppliers as $s)
								<tr class="border-t">
									<td class="py-2 pr-4 font-medium">{{ $s->supplier_code }}</td>
									<td class="py-2 pr-4">{{ $s->business_name }}</td>
									<td class="py-2 pr-4">{{ $s->contact_person }}</td>
									<td class="py-2 pr-4">{{ $s->email }}</td>
									<td class="py-2 pr-4">{{ ucfirst(str_replace('_',' ', $s->status)) }}</td>
									<td class="py-2 pr-4 text-right">
										<div class="inline-flex gap-2">
											@role('Executive Officer')
												<a href="{{ route('supply.suppliers.edit', $s) }}" class="px-2 py-1 bg-gray-100 rounded">Edit</a>
												@if(in_array($s->status, ['pending_verification','inactive']))
													<form method="POST" action="{{ route('supply.suppliers.approve', $s) }}">
														@csrf
														<button class="px-2 py-1 bg-green-600 text-white rounded">Approve</button>
													</form>
												@endif
											@endrole
										</div>
									</td>
								</tr>
							@empty
								<tr><td colspan="6" class="py-6 text-center text-gray-500">No suppliers found.</td></tr>
							@endforelse
						</tbody>
					</table>
					<div class="mt-4">{{ $suppliers->links() }}</div>
				</div>
			</div>
		</div>
	</div>
</x-app-layout>


