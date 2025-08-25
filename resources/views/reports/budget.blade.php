@section('title', 'Reports - Budget Utilization')

<x-app-layout>
	<x-slot name="header">
		<h2 class="font-semibold text-2xl text-gray-800 leading-tight">{{ __('Budget Utilization by Department') }}</h2>
	</x-slot>

	<div class="py-8">
		<div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
			<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
				<div class="p-6 text-gray-900">
					<div class="overflow-x-auto">
						<table class="min-w-full divide-y divide-gray-200">
							<thead class="bg-gray-50">
								<tr>
									<th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
									<th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">PR Count</th>
									<th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">PR Total</th>
									<th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">PO Count</th>
									<th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">PO Total</th>
									<th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Utilization</th>
								</tr>
							</thead>
							<tbody class="bg-white divide-y divide-gray-200">
								@forelse($rows as $r)
								<tr>
									<td class="px-4 py-2">{{ $r['department']->name }}</td>
									<td class="px-4 py-2">{{ $r['pr_count'] }}</td>
									<td class="px-4 py-2">₱{{ number_format($r['pr_total'], 2) }}</td>
									<td class="px-4 py-2">{{ $r['po_count'] }}</td>
									<td class="px-4 py-2">₱{{ number_format($r['po_total'], 2) }}</td>
									<td class="px-4 py-2">{{ $r['utilization_rate'] !== null ? $r['utilization_rate'] . '%' : '—' }}</td>
								</tr>
								@empty
								<tr>
									<td colspan="6" class="px-4 py-6 text-center text-gray-500">No budget data.</td>
								</tr>
								@endforelse
							</tbody>
						</table>
					</div>

					<div class="mt-4 text-sm text-gray-600">
						Totals: PR ₱{{ number_format($totals['pr_total'], 2) }} • PO ₱{{ number_format($totals['po_total'], 2) }} ({{ $totals['pr_count'] }} PRs, {{ $totals['po_count'] }} POs)
					</div>
				</div>
			</div>
		</div>
	</div>
</x-app-layout>


