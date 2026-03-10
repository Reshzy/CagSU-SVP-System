@section('title', 'Budget Office - Earmarking')

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Budget Office - Earmark Requests') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('status'))
                <div class="p-4 rounded-lg bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 text-green-800 dark:text-green-300 text-sm font-medium">
                    {{ session('status') }}
                </div>
            @endif

            {{-- Tab Navigation --}}
            <div x-data="{ activeTab: '{{ session('active_tab', 'pending') }}' }">
                <div class="border-b border-gray-200 dark:border-gray-700">
                    <nav class="-mb-px flex gap-6" aria-label="Tabs">
                        <button
                            @click="activeTab = 'pending'"
                            :class="activeTab === 'pending'
                                ? 'border-cagsu-maroon text-cagsu-maroon dark:text-white dark:border-white'
                                : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600'"
                            class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm flex items-center gap-2 transition-colors">
                            Pending Earmarks
                            @if($pendingRequests->total() > 0)
                                <span class="inline-flex items-center justify-center px-2 py-0.5 rounded-full text-xs font-bold bg-cagsu-maroon text-white">
                                    {{ $pendingRequests->total() }}
                                </span>
                            @endif
                        </button>
                        <button
                            @click="activeTab = 'earmarked'"
                            :class="activeTab === 'earmarked'
                                ? 'border-cagsu-maroon text-cagsu-maroon dark:text-white dark:border-white'
                                : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600'"
                            class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm flex items-center gap-2 transition-colors">
                            Earmarked
                            @if($earmarkedRequests->total() > 0)
                                <span class="inline-flex items-center justify-center px-2 py-0.5 rounded-full text-xs font-bold bg-gray-400 dark:bg-gray-600 text-white">
                                    {{ $earmarkedRequests->total() }}
                                </span>
                            @endif
                        </button>
                    </nav>
                </div>

                {{-- Pending Earmarks Tab --}}
                <div x-show="activeTab === 'pending'" x-cloak>
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-b-lg sm:rounded-tr-lg mt-0">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <div class="text-sm text-gray-600 dark:text-gray-400">
                                    @php
                                        $from = $pendingRequests->firstItem();
                                        $to = $pendingRequests->lastItem();
                                        $total = $pendingRequests->total();
                                    @endphp
                                    @if($total > 0)
                                        Showing {{ $from }}–{{ $to }} of {{ $total }} pending earmarks
                                    @else
                                        No pending earmarks
                                    @endif
                                </div>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">PR #</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Requester</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Department</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Purpose</th>
                                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Est. Total</th>
                                            <th class="px-4 py-3"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                        @forelse($pendingRequests as $req)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                            <td class="px-4 py-3 font-mono text-sm text-gray-900 dark:text-gray-100">{{ $req->pr_number }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">{{ $req->requester?->name }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $req->department?->name }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300 max-w-xs truncate">{{ $req->purpose }}</td>
                                            <td class="px-4 py-3 text-sm text-right text-gray-900 dark:text-gray-100 font-medium">₱{{ number_format((float)$req->estimated_total, 2) }}</td>
                                            <td class="px-4 py-3 text-right">
                                                <a href="{{ route('budget.purchase-requests.edit', $req) }}"
                                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-cagsu-yellow text-white text-sm font-medium rounded-md hover:opacity-90 transition-opacity">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                                    Review
                                                </a>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="6" class="px-4 py-10 text-center text-gray-500 dark:text-gray-400">
                                                <svg class="mx-auto h-10 w-10 text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                                No requests awaiting earmark.
                                            </td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-4">{{ $pendingRequests->appends(['earmarked_page' => $earmarkedRequests->currentPage()])->links() }}</div>
                        </div>
                    </div>
                </div>

                {{-- Earmarked Tab --}}
                <div x-show="activeTab === 'earmarked'" x-cloak>
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-b-lg sm:rounded-tr-lg mt-0">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <div class="text-sm text-gray-600 dark:text-gray-400">
                                    @php
                                        $eFrom = $earmarkedRequests->firstItem();
                                        $eTo = $earmarkedRequests->lastItem();
                                        $eTotal = $earmarkedRequests->total();
                                    @endphp
                                    @if($eTotal > 0)
                                        Showing {{ $eFrom }}–{{ $eTo }} of {{ $eTotal }} earmarked requests
                                    @else
                                        No earmarked requests yet
                                    @endif
                                </div>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Earmark #</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">PR #</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Requester</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Department</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total</th>
                                            <th class="px-4 py-3"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                        @forelse($earmarkedRequests as $req)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                            <td class="px-4 py-3 font-mono text-sm font-semibold text-green-700 dark:text-green-400">{{ $req->earmark_id }}</td>
                                            <td class="px-4 py-3 font-mono text-sm text-gray-700 dark:text-gray-300">{{ $req->pr_number }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">{{ $req->requester?->name }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $req->department?->name }}</td>
                                            <td class="px-4 py-3">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-300">
                                                    {{ ucwords(str_replace('_', ' ', $req->status)) }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-right text-gray-900 dark:text-gray-100 font-medium">₱{{ number_format((float)$req->estimated_total, 2) }}</td>
                                            <td class="px-4 py-3 text-right">
                                                <div class="flex items-center justify-end gap-2">
                                                    <a href="{{ route('budget.purchase-requests.amend', $req) }}"
                                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-500 text-white text-sm font-medium rounded-md hover:bg-amber-600 transition-colors">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                        Amend
                                                    </a>
                                                    <a href="{{ route('budget.purchase-requests.export-earmark', $req) }}"
                                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700 transition-colors">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                                        Export
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="7" class="px-4 py-10 text-center text-gray-500 dark:text-gray-400">
                                                <svg class="mx-auto h-10 w-10 text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                                No earmarked requests found.
                                            </td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-4">{{ $earmarkedRequests->appends(['pending_page' => $pendingRequests->currentPage()])->links() }}</div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
