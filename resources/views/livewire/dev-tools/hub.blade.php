<div class="space-y-6">
    {{-- Warning banner --}}
    <div class="sticky top-[calc(var(--app-sticky-header-offset,4rem)+0.5rem)] z-30 rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 shadow-sm dark:border-amber-700 dark:bg-amber-950/40">
        <div class="flex items-start gap-3">
            <svg class="mt-0.5 h-5 w-5 shrink-0 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
            </svg>
            <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold text-amber-900 dark:text-amber-200">Dev Tools — testing overrides</p>
                <p class="mt-0.5 text-xs text-amber-800 dark:text-amber-300/90">
                    Actions here bypass normal role gates. Use only for local/staging testing. Workflow jumps skip intermediate approvals.
                </p>
            </div>
        </div>
    </div>

    {{-- Context bar --}}
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div>
                <label for="fiscalYear" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-400">Fiscal year</label>
                <select
                    id="fiscalYear"
                    wire:model.live="fiscalYear"
                    class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-cagsu-maroon focus:ring-cagsu-maroon dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                >
                    @for ($year = (int) date('Y') + 1; $year >= 2020; $year--)
                        <option value="{{ $year }}">{{ $year }}</option>
                    @endfor
                </select>
            </div>
            <div class="sm:col-span-1 lg:col-span-2">
                <label for="departmentId" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-400">Department context</label>
                <select
                    id="departmentId"
                    wire:model.live="departmentId"
                    class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-cagsu-maroon focus:ring-cagsu-maroon dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                >
                    <option value="">Select department…</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department->id }}">{{ $department->code }} — {{ $department->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="border-b border-gray-200 dark:border-gray-700">
        <nav class="-mb-px flex flex-wrap gap-1" aria-label="Dev Tools tabs">
            @foreach ([
                'overview' => 'Overview',
                'create-pr' => 'Create PR',
                'budgets' => 'Budgets',
                'ppmp' => 'PPMP',
                'workflow' => 'Workflow',
            ] as $key => $label)
                <button
                    type="button"
                    wire:click="setTab('{{ $key }}')"
                    @class([
                        'whitespace-nowrap border-b-2 px-4 py-2.5 text-sm font-medium transition',
                        'border-cagsu-maroon text-cagsu-maroon' => $tab === $key,
                        'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' => $tab !== $key,
                    ])
                >
                    {{ $label }}
                </button>
            @endforeach
        </nav>
    </div>

    <div wire:loading.delay.class="opacity-60" class="transition-opacity">
        @if ($tab === 'overview')
            @include('livewire.dev-tools.partials.overview')
        @elseif ($tab === 'create-pr')
            @include('livewire.dev-tools.partials.create-pr')
        @elseif ($tab === 'budgets')
            @include('livewire.dev-tools.partials.budgets')
        @elseif ($tab === 'ppmp')
            @include('livewire.dev-tools.partials.ppmp')
        @elseif ($tab === 'workflow')
            @include('livewire.dev-tools.partials.workflow')
        @endif
    </div>
</div>
