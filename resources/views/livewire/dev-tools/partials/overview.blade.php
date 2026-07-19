<div class="space-y-6">
    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-700 dark:text-gray-300">Readiness checklist</h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Status for the selected department and fiscal year. Fix gaps before creating test PRs.
        </p>

        <ul class="mt-6 space-y-3">
            @php
                $checks = [
                    [
                        'ok' => $overview['has_department'],
                        'label' => 'Department selected',
                        'hint' => 'Pick a department in the context bar',
                        'tab' => null,
                    ],
                    [
                        'ok' => $overview['has_budget'],
                        'label' => 'Budget allocated',
                        'hint' => $overview['has_budget']
                            ? '₱'.number_format($overview['budget_allocated'], 2)
                            : 'Set an allocated budget first',
                        'tab' => 'budgets',
                    ],
                    [
                        'ok' => $overview['has_ppmp'],
                        'label' => 'PPMP exists',
                        'hint' => $overview['has_ppmp'] ? 'PPMP found for this year' : 'Import a PPMP CSV',
                        'tab' => 'ppmp',
                    ],
                    [
                        'ok' => $overview['ppmp_validated'],
                        'label' => 'PPMP validated',
                        'hint' => $overview['ppmp_validated'] ? 'Ready for PR item seeding' : 'Validate PPMP after import',
                        'tab' => 'ppmp',
                    ],
                    [
                        'ok' => $overview['requester_count'] > 0,
                        'label' => 'Requesters available',
                        'hint' => $overview['requester_count'].' End User / Dean user(s)',
                        'tab' => 'create-pr',
                    ],
                ];
            @endphp

            @foreach ($checks as $check)
                <li class="flex items-start gap-3 rounded-lg border border-gray-100 px-4 py-3 dark:border-gray-700">
                    @if ($check['ok'])
                        <span class="mt-0.5 flex h-5 w-5 items-center justify-center rounded-full bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        </span>
                    @else
                        <span class="mt-0.5 flex h-5 w-5 items-center justify-center rounded-full bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01"/></svg>
                        </span>
                    @endif
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $check['label'] }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $check['hint'] }}</p>
                    </div>
                    @if ($check['tab'] && ! $check['ok'])
                        <button
                            type="button"
                            wire:click="setTab('{{ $check['tab'] }}')"
                            class="text-xs font-semibold text-cagsu-maroon hover:text-cagsu-orange"
                        >
                            Fix
                        </button>
                    @endif
                </li>
            @endforeach
        </ul>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">PRs this year</p>
            <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">{{ $overview['recent_pr_count'] }}</p>
            <button type="button" wire:click="setTab('workflow')" class="mt-3 text-sm font-medium text-cagsu-maroon hover:text-cagsu-orange">
                Open workflow →
            </button>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Quick create</p>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">Create a PR as any requester in the selected department.</p>
            <button type="button" wire:click="setTab('create-pr')" class="mt-3 text-sm font-medium text-cagsu-maroon hover:text-cagsu-orange">
                Create PR →
            </button>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Budget shortcuts</p>
            <div class="mt-3 flex flex-wrap gap-2">
                <button
                    type="button"
                    wire:click="quickSetBudget(1000000)"
                    wire:confirm="Set selected department allocated budget to ₱1,000,000?"
                    class="rounded-lg bg-cagsu-maroon px-3 py-1.5 text-xs font-semibold text-white hover:bg-cagsu-orange"
                >
                    Set ₱1M
                </button>
                <button
                    type="button"
                    wire:click="setTab('budgets')"
                    class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700"
                >
                    Manage budgets
                </button>
            </div>
        </div>
    </div>
</div>
