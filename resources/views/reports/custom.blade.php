@section('title', 'Reports - Custom Builder')

<x-app-layout>
	<x-slot name="header">
		<h2 class="font-semibold text-2xl text-gray-800 leading-tight">{{ __('Custom Report Builder (PR)') }}</h2>
	</x-slot>

	<div class="py-8">
		<div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
			<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
				<div class="p-6 text-gray-900">
					<form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
						<div class="md:col-span-2">
							<label class="text-sm text-gray-600">Columns</label>
							<div class="grid grid-cols-2 gap-2 mt-1 p-3 border rounded-md">
								@foreach($available as $key => $label)
									<label class="text-xs flex items-center space-x-2">
										<input type="checkbox" name="columns[]" value="{{ $key }}" @checked(in_array($key, $selected))>
										<span>{{ $label }}</span>
									</label>
								@endforeach
							</div>
						</div>
						<div>
							<label class="text-sm text-gray-600">Status</label>
							<select name="status" class="mt-1 block w-full border-gray-300 rounded-md">
								<option value="">All</option>
								@foreach($allStatuses as $s)
									<option value="{{ $s }}" @selected(request('status')===$s)>{{ Str::title(str_replace('_',' ',$s)) }}</option>
								@endforeach
							</select>
						</div>
						<div>
							<label class="text-sm text-gray-600">Department</label>
							<select name="department_id" class="mt-1 block w-full border-gray-300 rounded-md">
								<option value="">All</option>
								@foreach($departments as $dept)
									<option value="{{ $dept->id }}" @selected(request('department_id')==$dept->id)>{{ $dept->name }}</option>
								@endforeach
							</select>
						</div>
						<div>
							<label class="text-sm text-gray-600">Date From</label>
							<input type="date" name="date_from" value="{{ request('date_from') }}" class="mt-1 block w-full border-gray-300 rounded-md" />
							<label class="text-sm text-gray-600 mt-2">Date To</label>
							<input type="date" name="date_to" value="{{ request('date_to') }}" class="mt-1 block w-full border-gray-300 rounded-md" />
						</div>
						<div class="flex items-end space-x-2">
							<x-primary-button>Apply</x-primary-button>
							<a href="{{ route('reports.custom.export', request()->query()) }}" class="px-3 py-2 bg-cagsu-yellow text-white rounded-md">Export CSV</a>
						</div>
					</form>

					<div class="overflow-x-auto">
						<table class="min-w-full divide-y divide-gray-200">
							<thead class="bg-gray-50">
								<tr>
									@foreach($selected as $col)
										<th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $available[$col] }}</th>
									@endforeach
								</tr>
							</thead>
							<tbody class="bg-white divide-y divide-gray-200">
								@forelse($requests as $pr)
								<tr>
									@foreach($selected as $col)
										<td class="px-4 py-2 text-sm">
											@switch($col)
												@case('pr_number') {{ $pr->pr_number }} @break
												@case('created_at') {{ $pr->created_at?->format('Y-m-d H:i') }} @break
												@case('requester') {{ $pr->requester?->name }} @break
												@case('department') {{ $pr->department?->name }} @break
												@case('purpose') {{ Str::limit($pr->purpose, 80) }} @break
												@case('date_needed') {{ $pr->date_needed?->format('Y-m-d') }} @break
												@case('priority') {{ $pr->priority }} @break
												@case('estimated_total') {{ number_format((float)$pr->estimated_total, 2) }} @break
												@case('status') {{ $pr->status }} @break
												@default —
											@endswitch
										</td>
									@endforeach
								</tr>
								@empty
								<tr>
									<td colspan="{{ count($selected) }}" class="px-4 py-6 text-center text-gray-500">No records.</td>
								</tr>
								@endforelse
							</tbody>
						</table>
					</div>

					<div class="mt-4">{{ $requests->links() }}</div>
				</div>
			</div>
		</div>
	</div>
</x-app-layout>


