@section('title', 'Supply Officer - Purchase Requests')

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-gray-800 leading-tight">{{ __('Supply Officer - Review Purchase Requests') }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="GET" class="mb-4">
                        <label class="text-sm text-gray-600 mr-2">Status:</label>
                        <select name="status" class="border-gray-300 rounded-md" onchange="this.form.submit()">
                            <option value="">Submitted & In Review</option>
                            <option value="submitted" @selected($statusFilter==='submitted')>Submitted</option>
                            <option value="supply_office_review" @selected($statusFilter==='supply_office_review')>Supply Office Review</option>
                            <option value="budget_office_review" @selected($statusFilter==='budget_office_review')>Budget Office Review</option>
                            <option value="rejected" @selected($statusFilter==='rejected')>Rejected</option>
                            <option value="cancelled" @selected($statusFilter==='cancelled')>Cancelled</option>
                        </select>
                    </form>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">PR #</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Requester</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Purpose</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-4 py-2"></th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($requests as $req)
                                <tr>
                                    <td class="px-4 py-2 font-mono">{{ $req->pr_number }}</td>
                                    <td class="px-4 py-2">{{ $req->requester?->name }}</td>
                                    <td class="px-4 py-2">{{ $req->department?->name }}</td>
                                    <td class="px-4 py-2">{{ $req->purpose }}</td>
                                    <td class="px-4 py-2 capitalize">{{ str_replace('_', ' ', $req->status) }}</td>
                                    <td class="px-4 py-2 text-right">
                                        <form action="{{ route('supply.purchase-requests.status', $req) }}" method="POST" class="inline-flex items-center space-x-2">
                                            @csrf
                                            @method('PUT')
                                            <select name="action" class="border-gray-300 rounded-md">
                                                <option value="start_review">Start Review</option>
                                                <option value="send_to_budget">Send to Budget</option>
                                                <option value="reject">Reject</option>
                                                <option value="cancel">Cancel</option>
                                            </select>
                                            <input type="text" name="notes" placeholder="Notes" class="border-gray-300 rounded-md" />
                                            <input type="text" name="rejection_reason" placeholder="Rejection reason" class="border-gray-300 rounded-md" />
                                            <x-primary-button>Apply</x-primary-button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-6 text-center text-gray-500">No purchase requests found.</td>
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


