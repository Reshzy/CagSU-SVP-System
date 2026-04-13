<div
    x-data="{
        prefsKey() {
            return `budget_department_table_prefs_${@js($fiscalYear)}`;
        },
        init() {
            try {
                const stored = JSON.parse(localStorage.getItem(this.prefsKey()) || '{}');

                if (stored.search && '{{ request('search') }}' === '') {
                    $wire.set('search', stored.search);
                }

                if (Array.isArray(stored.visibleColumns) && stored.visibleColumns.length > 0) {
                    $wire.set('visibleColumns', stored.visibleColumns);
                }
            } catch (e) {}

            this.$watch('$wire.search', value => this.savePrefs({ search: value }));
            this.$watch('$wire.visibleColumns', value => this.savePrefs({ visibleColumns: value }));
        },
        savePrefs(partial) {
            try {
                const existing = JSON.parse(localStorage.getItem(this.prefsKey()) || '{}');
                localStorage.setItem(this.prefsKey(), JSON.stringify(Object.assign({}, existing, partial)));
            } catch (e) {}
        }
    }"
    class="space-y-6"
>
    <div class="bg-white shadow-sm rounded-xl border border-gray-200">
        <div class="p-6 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                <div class="md:col-span-2">
                    <label for="budget-search" class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1.5">
                        Search Department
                    </label>
                    <input
                        id="budget-search"
                        type="search"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Search by department name or code"
                        class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-cagsu-maroon focus:ring-cagsu-maroon"
                    />
                </div>
                <div>
                    <label for="budget-per-page" class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1.5">
                        Rows Per Page
                    </label>
                    <select
                        id="budget-per-page"
                        wire:model.live="perPage"
                        class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-cagsu-maroon focus:ring-cagsu-maroon"
                    >
                        @foreach($perPageOptions as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex flex-wrap items-center gap-2">
                    @foreach($columnLabels as $columnKey => $columnLabel)
                        <button
                            type="button"
                            wire:click="toggleColumn('{{ $columnKey }}')"
                            class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold transition-colors {{ $this->isColumnVisible($columnKey) ? 'border-cagsu-maroon bg-cagsu-maroon/10 text-cagsu-maroon' : 'border-gray-300 bg-white text-gray-600' }}"
                        >
                            {{ $columnLabel }}
                        </button>
                    @endforeach
                </div>

                @if($search !== '')
                    <button
                        type="button"
                        wire:click="clearFilters"
                        x-on:click="savePrefs({ search: '', visibleColumns: $wire.visibleColumns })"
                        class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors"
                    >
                        Clear Search
                    </button>
                @endif
            </div>
        </div>
    </div>

    <div class="bg-white shadow-sm rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex flex-wrap items-center justify-between gap-3">
            @php
                $totalRows = method_exists($departments, 'total') ? $departments->total() : $departments->count();
                $firstItem = method_exists($departments, 'firstItem') ? $departments->firstItem() : null;
                $lastItem = method_exists($departments, 'lastItem') ? $departments->lastItem() : null;
            @endphp
            <div>
                <span class="text-sm font-semibold text-gray-700">
                    {{ $totalRows }} {{ Illuminate\Support\Str::plural('Department', $totalRows) }}
                </span>
                @if($totalRows > 0)
                    <span class="text-sm text-gray-400 ml-1">({{ $firstItem }}-{{ $lastItem }} shown)</span>
                @endif
            </div>
            <div class="text-sm text-gray-500" wire:loading.delay.short>Updating table...</div>
        </div>

        @if($departments->hasPages())
            <div class="px-6 py-4 border-b border-gray-100">
                {{ $departments->onEachSide(1)->links() }}
            </div>
        @endif

        @php
            $sortIcons = [
                'asc' => 'M5 15l7-7 7 7',
                'desc' => 'M19 9l-7 7-7-7',
            ];
        @endphp

        <div
            x-data="{
                atTop: true,
                atBottom: false,
                isScrollable: false,
                updateShadows(el) {
                    this.atTop = el.scrollTop === 0;
                    this.atBottom = Math.ceil(el.scrollTop + el.clientHeight) >= el.scrollHeight;
                    this.isScrollable = el.scrollHeight > el.clientHeight + 1;
                }
            }"
            x-init="updateShadows($refs.budgetDeptScroll)"
            @resize.window.debounce.50ms="updateShadows($refs.budgetDeptScroll)"
            class="relative overflow-hidden"
        >
            <div
                x-cloak
                x-show="isScrollable && !atTop"
                class="pointer-events-none absolute inset-x-0 top-0 h-4 bg-gradient-to-b from-gray-900/10 to-transparent z-20"
            ></div>
            <div
                x-cloak
                x-show="isScrollable && !atBottom"
                class="pointer-events-none absolute inset-x-0 bottom-0 h-4 bg-gradient-to-t from-gray-900/10 to-transparent z-20"
            ></div>

            <div class="max-h-[70vh] overflow-auto overscroll-contain" x-ref="budgetDeptScroll" @scroll.debounce.50ms="updateShadows($el)">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="sticky top-0 z-10 bg-gray-50 px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                <button type="button" wire:click="sortBy('department')" class="inline-flex items-center gap-2">
                                    <span>Department</span>
                                    @if($sortField === 'department')
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $sortIcons[$sortDirection] }}" />
                                        </svg>
                                    @endif
                                </button>
                            </th>
                            @if($this->isColumnVisible('code'))
                                <th class="sticky top-0 z-10 bg-gray-50 px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    <button type="button" wire:click="sortBy('code')" class="inline-flex items-center gap-2">
                                        <span>Code</span>
                                        @if($sortField === 'code')
                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $sortIcons[$sortDirection] }}" />
                                            </svg>
                                        @endif
                                    </button>
                                </th>
                            @endif
                            @if($this->isColumnVisible('allocated'))
                                <th class="sticky top-0 z-10 bg-gray-50 px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    <button type="button" wire:click="sortBy('allocated')" class="inline-flex items-center gap-2">
                                        <span>Allocated</span>
                                        @if($sortField === 'allocated')
                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $sortIcons[$sortDirection] }}" />
                                            </svg>
                                        @endif
                                    </button>
                                </th>
                            @endif
                            @if($this->isColumnVisible('utilized'))
                                <th class="sticky top-0 z-10 bg-gray-50 px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    <button type="button" wire:click="sortBy('utilized')" class="inline-flex items-center gap-2">
                                        <span>Utilized</span>
                                        @if($sortField === 'utilized')
                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $sortIcons[$sortDirection] }}" />
                                            </svg>
                                        @endif
                                    </button>
                                </th>
                            @endif
                            @if($this->isColumnVisible('reserved'))
                                <th class="sticky top-0 z-10 bg-gray-50 px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    <button type="button" wire:click="sortBy('reserved')" class="inline-flex items-center gap-2">
                                        <span>Reserved</span>
                                        @if($sortField === 'reserved')
                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $sortIcons[$sortDirection] }}" />
                                            </svg>
                                        @endif
                                    </button>
                                </th>
                            @endif
                            @if($this->isColumnVisible('available'))
                                <th class="sticky top-0 z-10 bg-gray-50 px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    <button type="button" wire:click="sortBy('available')" class="inline-flex items-center gap-2">
                                        <span>Available</span>
                                        @if($sortField === 'available')
                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $sortIcons[$sortDirection] }}" />
                                            </svg>
                                        @endif
                                    </button>
                                </th>
                            @endif
                            @if($this->isColumnVisible('utilization'))
                                <th class="sticky top-0 z-10 bg-gray-50 px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    <button type="button" wire:click="sortBy('utilization')" class="inline-flex items-center gap-2">
                                        <span>Utilization</span>
                                        @if($sortField === 'utilization')
                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $sortIcons[$sortDirection] }}" />
                                            </svg>
                                        @endif
                                    </button>
                                </th>
                            @endif
                            <th class="sticky top-0 z-10 bg-gray-50 px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @php
                            $visibleToggleCount = count(array_filter(array_keys($columnLabels), fn ($column) => $this->isColumnVisible($column)));
                            $colspan = 2 + $visibleToggleCount;
                        @endphp
                        @forelse($departments as $department)
                            <tr wire:key="department-budget-row-{{ $department->id }}" class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ $department->name }}</div>
                                </td>
                                @if($this->isColumnVisible('code'))
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $department->code }}</td>
                                @endif
                                @if($this->isColumnVisible('allocated'))
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-900">
                                        ₱{{ number_format((float) $department->allocated_budget, 2) }}
                                    </td>
                                @endif
                                @if($this->isColumnVisible('utilized'))
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-900">
                                        ₱{{ number_format((float) $department->utilized_budget, 2) }}
                                    </td>
                                @endif
                                @if($this->isColumnVisible('reserved'))
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-900">
                                        ₱{{ number_format((float) $department->reserved_budget, 2) }}
                                    </td>
                                @endif
                                @if($this->isColumnVisible('available'))
                                    @php
                                        $availableBudget = (float) $department->available_budget;
                                        $allocatedBudget = (float) $department->allocated_budget;
                                        $availableClass = 'text-green-600';

                                        if ($availableBudget < 0) {
                                            $availableClass = 'text-red-600';
                                        } elseif ($allocatedBudget > 0 && $availableBudget < $allocatedBudget * 0.1) {
                                            $availableClass = 'text-orange-600';
                                        }
                                    @endphp
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-semibold {{ $availableClass }}">
                                        ₱{{ number_format($availableBudget, 2) }}
                                    </td>
                                @endif
                                @if($this->isColumnVisible('utilization'))
                                    @php
                                        $utilization = (float) $department->utilization_percentage;
                                        $progressBarClass = 'accent-green-600';

                                        if ($utilization >= 90) {
                                            $progressBarClass = 'accent-red-600';
                                        } elseif ($utilization >= 70) {
                                            $progressBarClass = 'accent-orange-600';
                                        }
                                    @endphp
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <progress value="{{ min($utilization, 100.0) }}" max="100" class="w-24 h-2 {{ $progressBarClass }}"></progress>
                                            <span class="text-sm text-gray-600">{{ number_format($utilization, 1) }}%</span>
                                        </div>
                                    </td>
                                @endif
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                    <div class="inline-flex items-center justify-center gap-2">
                                        <div class="relative group">
                                            <a
                                                href="{{ route('budget.edit', ['department' => $department->id, 'fiscal_year' => $fiscalYear]) }}"
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-indigo-200 bg-indigo-50 text-indigo-700 transition hover:bg-indigo-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1"
                                                aria-label="Set budget"
                                            >
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </a>
                                            <span class="pointer-events-none absolute left-1/2 top-0 z-20 -translate-x-1/2 -translate-y-[calc(100%+0.55rem)] whitespace-nowrap rounded-md bg-gray-900 px-2.5 py-1 text-xs font-semibold text-white opacity-0 shadow-sm transition group-hover:opacity-100 group-focus-within:opacity-100">
                                                Set Budget
                                                <span class="absolute left-1/2 top-full h-2 w-2 -translate-x-1/2 -translate-y-1/2 rotate-45 bg-gray-900"></span>
                                            </span>
                                        </div>

                                        <div class="relative group">
                                            <a
                                                href="{{ route('budget.show', ['department' => $department->id, 'fiscal_year' => $fiscalYear]) }}"
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-blue-200 bg-blue-50 text-blue-700 transition hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1"
                                                aria-label="View details"
                                            >
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            </a>
                                            <span class="pointer-events-none absolute left-1/2 top-0 z-20 -translate-x-1/2 -translate-y-[calc(100%+0.55rem)] whitespace-nowrap rounded-md bg-gray-900 px-2.5 py-1 text-xs font-semibold text-white opacity-0 shadow-sm transition group-hover:opacity-100 group-focus-within:opacity-100">
                                                View Details
                                                <span class="absolute left-1/2 top-full h-2 w-2 -translate-x-1/2 -translate-y-1/2 rotate-45 bg-gray-900"></span>
                                            </span>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $colspan }}" class="px-6 py-10 text-center text-gray-500">
                                    No departments found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="bg-gray-50">
                        <tr class="font-semibold">
                            <td class="px-6 py-4 text-sm text-gray-900">TOTAL</td>
                            @if($this->isColumnVisible('code'))
                                <td class="px-6 py-4 text-sm text-gray-500">-</td>
                            @endif
                            @if($this->isColumnVisible('allocated'))
                                <td class="px-6 py-4 text-right text-sm text-gray-900">
                                    ₱{{ number_format($summaryTotals['allocated'], 2) }}
                                </td>
                            @endif
                            @if($this->isColumnVisible('utilized'))
                                <td class="px-6 py-4 text-right text-sm text-gray-900">
                                    ₱{{ number_format($summaryTotals['utilized'], 2) }}
                                </td>
                            @endif
                            @if($this->isColumnVisible('reserved'))
                                <td class="px-6 py-4 text-right text-sm text-gray-900">
                                    ₱{{ number_format($summaryTotals['reserved'], 2) }}
                                </td>
                            @endif
                            @if($this->isColumnVisible('available'))
                                <td class="px-6 py-4 text-right text-sm text-gray-900">
                                    ₱{{ number_format($summaryTotals['available'], 2) }}
                                </td>
                            @endif
                            @if($this->isColumnVisible('utilization'))
                                <td class="px-6 py-4 text-right text-sm text-gray-500">-</td>
                            @endif
                            <td class="px-6 py-4 text-sm text-gray-500"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        @if($departments->hasPages())
            <div class="px-6 py-4 border-t border-gray-100">
                {{ $departments->onEachSide(1)->links() }}
            </div>
        @endif
    </div>
</div>
