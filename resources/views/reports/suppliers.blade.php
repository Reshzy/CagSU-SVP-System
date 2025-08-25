@section('title', 'Reports - Supplier Performance')

<x-app-layout>
	<x-slot name="header">
		<h2 class="font-semibold text-2xl text-gray-800 leading-tight">{{ __('Supplier Performance') }}</h2>
	</x-slot>

	<div class="py-8">
		<div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
			<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
				<div class="p-6 text-gray-900">
					<div class="overflow-x-auto">
						<table class="min-w-full divide-y divide-gray-200">
							<thead class="bg-gray-50">
								<tr>
									<th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Supplier</th>
									<th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quotes</th>
									<th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Awards</th>
									<th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Win Rate</th>
									<th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Awarded Value</th>
									<th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">POs</th>
									<th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Completed</th>
									<th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">PO Value</th>
								</tr>
							</thead>
							<tbody class="bg-white divide-y divide-gray-200">
								@forelse($rows as $r)
								<tr>
									<td class="px-4 py-2">{{ $r['supplier']->business_name }}</td>
									<td class="px-4 py-2">{{ $r['total_quotes'] }}</td>
									<td class="px-4 py-2">{{ $r['awards'] }}</td>
									<td class="px-4 py-2">{{ $r['win_rate'] !== null ? $r['win_rate'] . '%' : '—' }}</td>
									<td class="px-4 py-2">₱{{ number_format($r['awarded_total'], 2) }}</td>
									<td class="px-4 py-2">{{ $r['po_count'] }}</td>
									<td class="px-4 py-2">{{ $r['po_completed'] }}</td>
									<td class="px-4 py-2">₱{{ number_format($r['po_total'], 2) }}</td>
								</tr>
								@empty
								<tr>
									<td colspan="8" class="px-4 py-6 text-center text-gray-500">No supplier data.</td>
								</tr>
								@endforelse
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
	</div>
</x-app-layout>


