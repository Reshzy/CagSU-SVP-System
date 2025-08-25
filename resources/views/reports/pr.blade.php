@section('title', 'Reports - Purchase Requests')

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-gray-800 leading-tight">{{ __('Purchase Request Reports') }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-4">
                        <div>
                            <label class="text-sm text-gray-600">Status</label>
                            <select name="status" class="w-full border-gray-300 rounded-md">
                                <option value="">All</option>
                                @foreach(['draft','submitted','supply_office_review','budget_office_review','ceo_approval','bac_evaluation','bac_approved','po_generation','po_approved','supplier_processing','delivered','completed','cancelled','rejected'] as $s)
                                    <option value="{{ $s }}" @selected(($filters['status'] ?? '')===$s)>{{ Str::title(str_replace('_',' ',$s)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-sm text-gray-600">Department</label>
                            <select name="department_id" class="w-full border-gray-300 rounded-md">
                                <option value="">All</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}" @selected(($filters['department_id'] ?? '')==$dept->id)>{{ $dept->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-sm text-gray-600">From</label>
                            <input type="date" name="date_from" value="{{ optional($filters['date_from'])->format('Y-m-d') }}" class="w-full border-gray-300 rounded-md" />
                        </div>
                        <div>
                            <label class="text-sm text-gray-600">To</label>
                            <input type="date" name="date_to" value="{{ optional($filters['date_to'])->format('Y-m-d') }}" class="w-full border-gray-300 rounded-md" />
                        </div>
                        <div class="flex items-end space-x-2">
                            <x-primary-button>Filter</x-primary-button>
                            <a href="{{ route('reports.pr.export', request()->query()) }}" class="px-3 py-2 bg-cagsu-yellow text-white rounded-md">Export CSV</a>
                        </div>
                    </form>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">PR #</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Requester</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Purpose</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Needed</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Priority</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Est. Total</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($requests as $req)
                                <tr>
                                    <td class="px-4 py-2 font-mono">{{ $req->pr_number }}</td>
                                    <td class="px-4 py-2">{{ $req->created_at->format('Y-m-d H:i') }}</td>
                                    <td class="px-4 py-2">{{ $req->requester?->name }}</td>
                                    <td class="px-4 py-2">{{ $req->department?->name }}</td>
                                    <td class="px-4 py-2">{{ Str::limit($req->purpose, 60) }}</td>
                                    <td class="px-4 py-2">{{ optional($req->date_needed)->format('Y-m-d') }}</td>
                                    <td class="px-4 py-2 capitalize">{{ $req->priority }}</td>
                                    <td class="px-4 py-2">{{ number_format((float)$req->estimated_total, 2) }}</td>
                                    <td class="px-4 py-2 capitalize">{{ str_replace('_', ' ', $req->status) }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9" class="px-4 py-6 text-center text-gray-500">No records found.</td>
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


