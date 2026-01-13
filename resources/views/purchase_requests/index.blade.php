@section('title', 'Purchase Requests')

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-gray-800 leading-tight">{{ __('My Purchase Requests') }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <!-- Returned PRs Alert Section -->
            @if($returnedPrs->count() > 0)
            <div class="bg-orange-50 border-l-4 border-orange-500 rounded-lg shadow-sm">
                <div class="p-6">
                    <div class="flex items-center mb-4">
                        <svg class="w-6 h-6 text-orange-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        <div>
                            <h3 class="text-lg font-bold text-orange-900">Action Required: Returned Purchase Requests</h3>
                            <p class="text-sm text-orange-700">You have {{ $returnedPrs->count() }} {{ Str::plural('PR', $returnedPrs->count()) }} that need your attention</p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        @foreach($returnedPrs as $returned)
                        <div class="bg-white rounded-lg border border-orange-200 p-5">
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3 mb-2">
                                        <a href="{{ route('purchase-requests.show', $returned) }}" class="font-mono text-lg font-bold text-cagsu-maroon hover:text-cagsu-orange">
                                            {{ $returned->pr_number }}
                                        </a>
                                        <span class="px-3 py-1 text-xs font-semibold rounded-full bg-orange-100 text-orange-800">
                                            Returned by Supply Office
                                        </span>
                                    </div>
                                    <p class="text-gray-900 font-medium">{{ $returned->purpose }}</p>
                                    <p class="text-sm text-gray-500 mt-1">Submitted {{ $returned->submitted_at?->format('M d, Y') ?? $returned->created_at->format('M d, Y') }}</p>
                                </div>
                            </div>

                            @if($returned->return_remarks)
                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
                                <div class="flex items-start gap-2">
                                    <svg class="w-5 h-5 text-yellow-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
                                    </svg>
                                    <div class="flex-1">
                                        <p class="text-xs font-semibold text-yellow-900 mb-1">Supply Officer's Remarks:</p>
                                        <p class="text-sm text-yellow-900">{{ $returned->return_remarks }}</p>
                                        @if($returned->returnedBy)
                                        <p class="text-xs text-yellow-700 mt-2">
                                            Returned by {{ $returned->returnedBy->name }} on {{ $returned->returned_at?->format('M d, Y h:i A') }}
                                        </p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @endif

                            <div class="flex gap-3">
                                <a href="{{ route('purchase-requests.show', $returned) }}" class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                    View Details
                                </a>
                                <a href="{{ route('purchase-requests.replacement.create', $returned) }}" class="inline-flex items-center px-4 py-2 bg-cagsu-maroon text-white rounded-lg hover:bg-cagsu-orange transition font-medium">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    Create Replacement PR
                                </a>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            <!-- All Purchase Requests -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex items-center justify-between mb-4">
                        <div class="text-lg font-semibold">All Purchase Requests</div>
                        <a href="{{ route('purchase-requests.create') }}" class="inline-flex items-center px-4 py-2 bg-cagsu-yellow text-white rounded-md hover:opacity-90">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
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
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($requests as $req)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        <a href="{{ route('purchase-requests.show', $req) }}" class="font-mono font-semibold text-cagsu-maroon hover:text-cagsu-orange">
                                            {{ $req->pr_number }}
                                        </a>
                                    </td>
                                    <td class="px-4 py-3">
                                        <p class="font-medium text-gray-900">{{ Str::limit($req->purpose, 50) }}</p>
                                        @if($req->replacesPr)
                                        <p class="text-xs text-gray-500 mt-1">
                                            <span class="inline-flex items-center">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                                </svg>
                                                Replaces {{ $req->replacesPr->pr_number }}
                                            </span>
                                        </p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        {{ $req->date_needed?->format('M d, Y') ?? 'Not set' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        @php
                                            $statusColors = [
                                                'draft' => 'bg-gray-100 text-gray-800',
                                                'submitted' => 'bg-blue-100 text-blue-800',
                                                'supply_office_review' => 'bg-yellow-100 text-yellow-800',
                                                'budget_office_review' => 'bg-purple-100 text-purple-800',
                                                'bac_evaluation' => 'bg-orange-100 text-orange-800',
                                                'bac_approved' => 'bg-green-100 text-green-800',
                                                'completed' => 'bg-green-100 text-green-800',
                                                'rejected' => 'bg-red-100 text-red-800',
                                                'cancelled' => 'bg-gray-100 text-gray-800',
                                            ];
                                            $statusColor = $statusColors[$req->status] ?? 'bg-gray-100 text-gray-800';
                                            $statusDisplay = $req->status === 'rejected' ? 'Deferred' : str_replace('_', ' ', Str::title($req->status));
                                        @endphp
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $statusColor }}">
                                            {{ $statusDisplay }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <a href="{{ route('purchase-requests.show', $req) }}" class="text-sm text-cagsu-maroon hover:text-cagsu-orange font-medium">
                                            View →
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-12 text-center text-gray-500">
                                        <svg class="mx-auto h-12 w-12 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        <p class="text-sm">No purchase requests yet.</p>
                                        <a href="{{ route('purchase-requests.create') }}" class="text-sm text-cagsu-maroon hover:text-cagsu-orange font-medium mt-2 inline-block">
                                            Create your first PR →
                                        </a>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($requests->hasPages())
                    <div class="mt-4">
                        {{ $requests->links() }}
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>


