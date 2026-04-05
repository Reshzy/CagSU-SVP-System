<div class="space-y-6">

    {{-- Tab bar --}}
    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="flex border-b border-gray-200 dark:border-gray-700">
            <button
                type="button"
                wire:click="setTab('departments')"
                class="relative inline-flex items-center gap-2 px-6 py-4 text-sm font-semibold transition-colors focus:outline-none
                    {{ $tab === 'departments'
                        ? 'text-cagsu-maroon border-b-2 border-cagsu-maroon bg-cagsu-maroon/5 dark:bg-cagsu-maroon/10 dark:text-red-400'
                        : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700/50' }}"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
                Departments
            </button>
            <button
                type="button"
                wire:click="setTab('requests')"
                class="relative inline-flex items-center gap-2 px-6 py-4 text-sm font-semibold transition-colors focus:outline-none
                    {{ $tab === 'requests'
                        ? 'text-cagsu-maroon border-b-2 border-cagsu-maroon bg-cagsu-maroon/5 dark:bg-cagsu-maroon/10 dark:text-red-400'
                        : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700/50' }}"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                Requests
                @if($pendingCount > 0)
                    <span class="inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-cagsu-yellow px-1.5 text-xs font-bold text-cagsu-maroon">
                        {{ $pendingCount }}
                    </span>
                @endif
            </button>
        </div>

        {{-- Filter bar --}}
        <div class="p-6">
            <div class="flex flex-col sm:flex-row gap-4 items-end">
                <div class="flex-1">
                    <label for="dept-search" class="block text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wide mb-1.5">Search</label>
                    <input
                        id="dept-search"
                        type="search"
                        wire:model.live.debounce.400ms="search"
                        placeholder="{{ $tab === 'departments' ? 'Search by name or code…' : 'Search by name, code, or email…' }}"
                        class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm shadow-sm focus:border-cagsu-maroon focus:ring-cagsu-maroon"
                    />
                </div>

                @if($tab === 'requests')
                    <div>
                        <label for="dept-status" class="block text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wide mb-1.5">Status</label>
                        <select
                            id="dept-status"
                            wire:model.live="status"
                            class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm shadow-sm focus:border-cagsu-maroon focus:ring-cagsu-maroon"
                        >
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                            <option value="">All</option>
                        </select>
                    </div>
                @endif

                @if($tab === 'departments')
                    <a
                        href="{{ route('ceo.departments.create') }}"
                        class="inline-flex items-center gap-2 rounded-lg bg-cagsu-maroon px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-900 focus:outline-none focus:ring-2 focus:ring-cagsu-maroon focus:ring-offset-2 transition-colors shadow-sm whitespace-nowrap"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        New Department
                    </a>
                @endif
            </div>

            <div class="mt-3 flex items-center justify-between gap-2">
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    <span wire:loading.delay.short class="italic">Updating…</span>
                </div>
                @if($search !== '' || ($tab === 'requests' && $status !== 'pending'))
                    <button
                        type="button"
                        wire:click="clearFilters"
                        class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-1.5 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Clear filters
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{-- ─── DEPARTMENTS tab ─────────────────────────────────────────────────────── --}}
    @if($tab === 'departments')
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between gap-4">
                @php
                    $deptsTotal = $departments ? (method_exists($departments, 'total') ? $departments->total() : $departments->count()) : 0;
                    $deptsFirst = $departments && method_exists($departments, 'firstItem') ? $departments->firstItem() : null;
                    $deptsLast  = $departments && method_exists($departments, 'lastItem') ? $departments->lastItem() : null;
                @endphp
                <div>
                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                        {{ $deptsTotal }} {{ Str::plural('Department', $deptsTotal) }}
                    </span>
                    @if($deptsTotal > 0 && $deptsFirst && $deptsLast)
                        <span class="text-sm text-gray-400 dark:text-gray-500 ml-1">({{ $deptsFirst }}–{{ $deptsLast }} shown)</span>
                    @endif
                </div>
            </div>

            @if(session('status'))
                <div class="mx-6 mt-4 flex items-center gap-3 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 px-4 py-3 text-sm text-green-800 dark:text-green-300">
                    <svg class="h-4 w-4 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    {{ session('status') }}
                </div>
            @endif

            @if($departments && $departments->hasPages())
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                    {{ $departments->links() }}
                </div>
            @endif

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Code</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Head</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Contact</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700 bg-white dark:bg-gray-800">
                        @forelse($departments as $dept)
                            <tr wire:key="dept-row-{{ $dept->id }}" class="hover:bg-gray-50/70 dark:hover:bg-gray-700/40 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="font-medium text-gray-900 dark:text-white text-sm">{{ $dept->name }}</div>
                                    @if($dept->description)
                                        <div class="text-xs text-gray-400 dark:text-gray-500 mt-0.5 line-clamp-1">{{ $dept->description }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <span class="font-mono text-xs bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 px-1.5 py-0.5 rounded">{{ $dept->code }}</span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">
                                    {{ $dept->head_name ?? '—' }}
                                </td>
                                <td class="px-6 py-4">
                                    @if($dept->contact_email)
                                        <div class="text-sm text-gray-700 dark:text-gray-300">{{ $dept->contact_email }}</div>
                                    @endif
                                    @if($dept->contact_phone)
                                        <div class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ $dept->contact_phone }}</div>
                                    @endif
                                    @if(! $dept->contact_email && ! $dept->contact_phone)
                                        <span class="text-sm text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    @if($dept->is_active)
                                        <span class="inline-flex items-center rounded-full bg-green-100 dark:bg-green-900/30 px-2.5 py-0.5 text-xs font-semibold text-green-800 dark:text-green-400">Active</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-gray-100 dark:bg-gray-700 px-2.5 py-0.5 text-xs font-semibold text-gray-600 dark:text-gray-400">Inactive</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <a
                                        href="{{ route('ceo.departments.edit', $dept) }}"
                                        class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-1.5 text-xs font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-cagsu-maroon focus:ring-offset-1 transition-colors shadow-sm"
                                    >
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                        Edit
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-16 text-center">
                                    <div class="flex flex-col items-center gap-3">
                                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-700">
                                            <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                            </svg>
                                        </div>
                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-200">No departments found</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">Try adjusting your search or create a new department.</div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($departments && $departments->hasPages())
                <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">
                    {{ $departments->links() }}
                </div>
            @endif
        </div>
    @endif

    {{-- ─── REQUESTS tab ────────────────────────────────────────────────────────── --}}
    @if($tab === 'requests')
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between gap-4">
                @php
                    $reqTotal = $departmentRequests ? (method_exists($departmentRequests, 'total') ? $departmentRequests->total() : $departmentRequests->count()) : 0;
                    $reqFirst = $departmentRequests && method_exists($departmentRequests, 'firstItem') ? $departmentRequests->firstItem() : null;
                    $reqLast  = $departmentRequests && method_exists($departmentRequests, 'lastItem') ? $departmentRequests->lastItem() : null;
                @endphp
                <div>
                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                        {{ $reqTotal }} {{ Str::plural('Request', $reqTotal) }}
                    </span>
                    @if($reqTotal > 0 && $reqFirst && $reqLast)
                        <span class="text-sm text-gray-400 dark:text-gray-500 ml-1">({{ $reqFirst }}–{{ $reqLast }} shown)</span>
                    @endif
                </div>
                @if($pendingCount > 0 && $status !== 'pending')
                    <span class="inline-flex items-center rounded-full bg-yellow-100 dark:bg-yellow-900/30 px-2.5 py-0.5 text-xs font-semibold text-yellow-800 dark:text-yellow-400">
                        {{ $pendingCount }} awaiting review
                    </span>
                @endif
            </div>

            @if(session('status'))
                <div class="mx-6 mt-4 flex items-center gap-3 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 px-4 py-3 text-sm text-green-800 dark:text-green-300">
                    <svg class="h-4 w-4 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    {{ session('status') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mx-6 mt-4 flex items-center gap-3 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 px-4 py-3 text-sm text-red-800 dark:text-red-300">
                    <svg class="h-4 w-4 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    {{ session('error') }}
                </div>
            @endif

            @if($departmentRequests && $departmentRequests->hasPages())
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                    {{ $departmentRequests->links() }}
                </div>
            @endif

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Code</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Requester Email</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Submitted</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700 bg-white dark:bg-gray-800">
                        @forelse($departmentRequests as $dr)
                            @php
                                $statusBadge = match($dr->status) {
                                    'approved' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                                    'rejected' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                                    default    => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
                                };
                            @endphp
                            <tr wire:key="req-row-{{ $dr->id }}" class="hover:bg-gray-50/70 dark:hover:bg-gray-700/40 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="font-medium text-gray-900 dark:text-white text-sm">{{ $dr->name }}</div>
                                    @if($dr->head_name)
                                        <div class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Head: {{ $dr->head_name }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <span class="font-mono text-xs bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 px-1.5 py-0.5 rounded">{{ strtoupper($dr->code) }}</span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                    {{ $dr->requester_email ?? '—' }}
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $statusBadge }}">
                                        {{ ucfirst($dr->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                    <span title="{{ $dr->created_at->format('M d, Y g:i A') }}">{{ $dr->created_at->diffForHumans() }}</span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <a
                                        href="{{ route('ceo.department-requests.show', $dr) }}"
                                        class="inline-flex items-center gap-1.5 rounded-lg bg-cagsu-maroon px-3 py-1.5 text-xs font-semibold text-white hover:bg-red-900 focus:outline-none focus:ring-2 focus:ring-cagsu-maroon focus:ring-offset-1 transition-colors shadow-sm"
                                    >
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        Review
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-16 text-center">
                                    <div class="flex flex-col items-center gap-3">
                                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-700">
                                            <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                            </svg>
                                        </div>
                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-200">No requests found</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">Try adjusting your filters.</div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($departmentRequests && $departmentRequests->hasPages())
                <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">
                    {{ $departmentRequests->links() }}
                </div>
            @endif
        </div>
    @endif
</div>
