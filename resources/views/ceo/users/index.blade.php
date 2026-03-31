<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white leading-tight">User Management</h2>
                <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">Review, approve, or defer user registrations.</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Filters --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide">Filter Users</h3>
                </div>
                <div class="p-6">
                    <form method="GET" action="{{ route('ceo.users.index') }}">
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
                            <div>
                                <label for="status" class="block text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wide mb-1.5">Status</label>
                                <select id="status" name="status"
                                    class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm shadow-sm focus:border-cagsu-maroon focus:ring-cagsu-maroon">
                                    <option value="">All Statuses</option>
                                    <option value="pending" @selected(($status ?? null) === 'pending')>Pending</option>
                                    <option value="approved" @selected(($status ?? null) === 'approved')>Approved</option>
                                    <option value="rejected" @selected(($status ?? null) === 'rejected')>Rejected / Deferred</option>
                                </select>
                            </div>
                            <div>
                                <label for="department_id" class="block text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wide mb-1.5">Department</label>
                                <select id="department_id" name="department_id"
                                    class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm shadow-sm focus:border-cagsu-maroon focus:ring-cagsu-maroon">
                                    <option value="">All Departments</option>
                                    @isset($departments)
                                        @foreach($departments as $dept)
                                            <option value="{{ $dept->id }}" @selected(($departmentId ?? null) == $dept->id)>{{ $dept->name }}</option>
                                        @endforeach
                                    @endisset
                                </select>
                            </div>
                            <div class="flex gap-2 sm:col-span-2 lg:col-span-2">
                                <button type="submit"
                                    class="flex-1 inline-flex items-center justify-center gap-2 rounded-lg bg-cagsu-maroon px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-900 focus:outline-none focus:ring-2 focus:ring-cagsu-maroon focus:ring-offset-2 transition-colors">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z" />
                                    </svg>
                                    Apply Filters
                                </button>
                                <a href="{{ route('ceo.users.index', ['reset' => 1]) }}"
                                    class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    Reset
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Results --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">

                {{-- Table header --}}
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between gap-4">
                    @php
                        $usersTotal = method_exists($users, 'total') ? $users->total() : (method_exists($users, 'count') ? $users->count() : 0);
                        $firstItem = method_exists($users, 'firstItem') ? $users->firstItem() : null;
                        $lastItem = method_exists($users, 'lastItem') ? $users->lastItem() : null;
                    @endphp
                    <div>
                        <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                            {{ $usersTotal }} {{ Str::plural('User', $usersTotal) }}
                        </span>
                        @if($usersTotal > 0)
                            <span class="text-sm text-gray-400 dark:text-gray-500 ml-1">
                                ({{ $firstItem }}–{{ $lastItem }} shown)
                            </span>
                        @endif
                    </div>

                    @if(($status ?? null) || ($departmentId ?? null))
                        <div class="flex items-center gap-2 flex-wrap">
                            @if($status ?? null)
                                @php
                                    $statusColors = ['pending' => 'bg-yellow-100 text-yellow-800', 'approved' => 'bg-green-100 text-green-800', 'rejected' => 'bg-red-100 text-red-800'];
                                @endphp
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $statusColors[$status] ?? 'bg-gray-100 text-gray-700' }}">
                                    {{ ucfirst($status) }}
                                </span>
                            @endif
                            @if($departmentId ?? null)
                                <span class="inline-flex items-center rounded-full bg-blue-100 text-blue-800 px-2.5 py-0.5 text-xs font-semibold">
                                    {{ optional($departments->firstWhere('id', $departmentId))->name ?? 'Dept #'.$departmentId }}
                                </span>
                            @endif
                        </div>
                    @endif
                </div>

                @if($users->hasPages())
                    <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                        {{ $users->links() }}
                    </div>
                @endif

                {{-- Flash --}}
                @if(session('status'))
                    <div class="mx-6 mt-4 flex items-center gap-3 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 px-4 py-3 text-sm text-green-800 dark:text-green-300">
                        <svg class="h-4 w-4 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        {{ session('status') }}
                    </div>
                @endif

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">User</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Department</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Employee ID</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Registered</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700 bg-white dark:bg-gray-800">
                            @forelse($users as $user)
                                <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-700/40 transition-colors">
                                    {{-- User info --}}
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-cagsu-maroon/10 text-cagsu-maroon font-semibold text-sm select-none">
                                                {{ strtoupper(mb_substr($user->name, 0, 1)) }}
                                            </div>
                                            <div>
                                                <div class="font-medium text-gray-900 dark:text-white text-sm">{{ $user->name }}</div>
                                                <div class="text-xs text-gray-400 dark:text-gray-500">{{ $user->email }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">
                                        {{ optional($user->department)->name ?? '—' }}
                                    </td>
                                    <td class="px-6 py-4">
                                        @if($user->employee_id)
                                            <span class="font-mono text-xs bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 px-1.5 py-0.5 rounded">{{ $user->employee_id }}</span>
                                        @else
                                            <span class="text-sm text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        @php
                                            $badgeMap = [
                                                'pending'  => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
                                                'approved' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                                                'rejected' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                                            ];
                                            $badgeClass = $badgeMap[$user->approval_status] ?? 'bg-gray-100 text-gray-700';
                                        @endphp
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $badgeClass }}">
                                            {{ ucfirst($user->approval_status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                        <span title="{{ $user->created_at->format('M d, Y g:i A') }}">{{ $user->created_at->diffForHumans() }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <a href="{{ route('ceo.users.show', $user) }}"
                                            class="inline-flex items-center gap-1.5 rounded-lg bg-cagsu-maroon px-3 py-1.5 text-xs font-semibold text-white hover:bg-red-900 focus:outline-none focus:ring-2 focus:ring-cagsu-maroon focus:ring-offset-1 transition-colors shadow-sm">
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
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                                </svg>
                                            </div>
                                            <div class="text-sm font-medium text-gray-900 dark:text-gray-200">No users found</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">Try adjusting your filters.</div>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($users->hasPages())
                    <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">
                        {{ $users->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
