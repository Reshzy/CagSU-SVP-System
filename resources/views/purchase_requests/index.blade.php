@section('title', 'Purchase Requests')

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-gray-800 leading-tight">{{ __('My Purchase Requests') }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex items-center justify-between mb-4">
                        <div class="text-lg font-semibold">List</div>
                        <a href="{{ route('purchase-requests.create') }}" class="inline-flex items-center px-4 py-2 bg-cagsu-yellow text-white rounded-md hover:opacity-90">
                            New PR
                        </a>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">PR #</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Purpose</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Needed</th>
                                    <!-- <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Priority</th> -->
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-4 py-2"></th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($requests as $req)
                                <tr>
                                    <td class="px-4 py-2 font-mono">{{ $req->pr_number }}</td>
                                    <td class="px-4 py-2">{{ $req->purpose }}</td>
                                    <td class="px-4 py-2">{{ $req->date_needed?->format('M d, Y') }}</td>
                                    <!-- <td class="px-4 py-2 capitalize">{{ $req->priority }}</td> -->
                                    <td class="px-4 py-2 capitalize">{{ str_replace('_', ' ', $req->status) }}</td>
                                    <td class="px-4 py-2 text-right">
                                        <span class="text-xs text-gray-500">Updated {{ $req->updated_at->diffForHumans() }}</span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-6 text-center text-gray-500">No purchase requests yet.</td>
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


