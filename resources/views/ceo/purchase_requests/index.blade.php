@section('title', 'CEO - Approvals')

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-gray-800 leading-tight">{{ __('CEO - Approve Purchase Requests') }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if(session('status'))
                    <div class="mb-4 p-3 rounded-md bg-green-50 text-green-700">{{ session('status') }}</div>
                    @endif

                    <div class="flex items-center justify-between mb-4">
                        <div class="text-sm text-gray-600">
                            @php
                            $from = $requests->firstItem();
                            $to = $requests->lastItem();
                            $total = $requests->total();
                            @endphp
                            Showing {{ $from }}–{{ $to }} of {{ $total }} awaiting CEO approval
                        </div>
                        <div class="text-sm text-gray-600">
                            Pending: {{ $requests->total() }}
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">PR #</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Requester</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Purpose</th>
                                    <!-- <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Justification</th> -->
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($requests as $req)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-4 py-3 font-mono text-sm">{{ $req->pr_number }}</td>
                                    <td class="px-4 py-3 text-sm">{{ $req->requester?->name }}</td>
                                    <td class="px-4 py-3 text-sm">{{ $req->department?->name }}</td>
                                    <td class="px-4 py-3 text-sm">
                                        <div class="max-w-xs truncate" title="{{ $req->purpose }}">
                                            {{ $req->purpose }}
                                        </div>
                                    </td>
                                    <!-- <td class="px-4 py-3 text-sm text-gray-600">
                                        <div class="max-w-xs truncate" title="{{ $req->justification }}">
                                            {{ $req->justification ?: 'N/A' }}
                                        </div>
                                    </td> -->
                                    <td class="px-4 py-3 text-sm font-medium">₱{{ number_format((float)$req->estimated_total, 2) }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="flex items-center justify-center space-x-2">
                                            <!-- View Details Button -->
                                            <a href="{{ route('ceo.purchase-requests.show', $req) }}"
                                                class="inline-flex items-center justify-center p-2 bg-blue-100 text-blue-700 rounded-md hover:bg-blue-200 transition-colors"
                                                title="View Details">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                    <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                                    <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                                                </svg>
                                            </a>

                                            <!-- Quick Approve Button -->
                                            <form action="{{ route('ceo.purchase-requests.update', $req) }}" method="POST" class="inline-block">
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="comments" value="" />
                                                <button name="decision" value="approve"
                                                    class="inline-flex items-center justify-center p-2 bg-green-100 text-green-700 rounded-md hover:bg-green-200 transition-colors"
                                                    title="Quick Approve"
                                                    onclick="return confirm('Are you sure you want to approve {{ $req->pr_number }}?')">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                    </svg>
                                                </button>
                                            </form>

                                            <!-- Defer Button - Routes to Details Page -->
                                            <a href="{{ route('ceo.purchase-requests.show', ['purchaseRequest' => $req, 'action' => 'reject']) }}"
                                                class="inline-flex items-center justify-center p-2 bg-red-100 text-red-700 rounded-md hover:bg-red-200 transition-colors"
                                                title="Defer - Provide deferral reason">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                </svg>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-6 text-center text-gray-500">No requests awaiting CEO approval.</td>
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