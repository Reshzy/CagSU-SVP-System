<div class="space-y-6">
    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-700 dark:text-gray-300">Import PPMP CSV</h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Imports into the department selected in the context bar for fiscal year {{ $fiscalYear }}.
        </p>

        <div class="mt-6 space-y-4">
            <div>
                <label for="csvFile" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-400">PS DBMS CSV file</label>
                <input
                    id="csvFile"
                    type="file"
                    wire:model="csvFile"
                    accept=".csv,.txt"
                    class="block w-full text-sm text-gray-700 file:mr-4 file:rounded-lg file:border-0 file:bg-cagsu-maroon file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-cagsu-orange dark:text-gray-300"
                />
                <div wire:loading wire:target="csvFile" class="mt-1 text-xs text-gray-500">Uploading…</div>
                @error('csvFile') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                @error('departmentId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <button
                type="button"
                wire:click="importPpmp"
                wire:loading.attr="disabled"
                class="rounded-lg bg-cagsu-maroon px-4 py-2 text-sm font-semibold text-white hover:bg-cagsu-orange disabled:opacity-60"
            >
                <span wire:loading.remove wire:target="importPpmp">Import CSV</span>
                <span wire:loading wire:target="importPpmp">Importing…</span>
            </button>
        </div>

        @if ($importOutput)
            <div class="mt-6 rounded-lg border border-gray-300 bg-gray-100 p-4 dark:border-gray-600 dark:bg-gray-900">
                <h4 class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-400">Import output</h4>
                <pre class="whitespace-pre-wrap text-xs text-gray-700 dark:text-gray-300">{{ $importOutput }}</pre>
            </div>
        @endif
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-700 dark:text-gray-300">Current PPMP</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    @if ($ppmp)
                        Status: <span class="font-semibold {{ $ppmp->status === 'validated' ? 'text-green-700 dark:text-green-400' : 'text-amber-700 dark:text-amber-400' }}">{{ $ppmp->status }}</span>
                        · {{ $ppmp->items->count() }} item(s)
                        · ₱{{ number_format((float) $ppmp->total_estimated_cost, 2) }}
                    @else
                        No PPMP for this department / year.
                    @endif
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                @if ($ppmp)
                    <button
                        type="button"
                        wire:click="validatePpmp"
                        wire:loading.attr="disabled"
                        @disabled($ppmp->status === 'validated')
                        class="rounded-lg bg-cagsu-maroon px-3 py-2 text-xs font-semibold text-white hover:bg-cagsu-orange disabled:opacity-40"
                    >
                        Validate PPMP
                    </button>
                    <a
                        href="{{ route('ppmp.summary', $ppmp) }}"
                        class="rounded-lg border border-gray-300 px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700"
                    >
                        Open summary
                    </a>
                @endif
            </div>
        </div>

        @if ($ppmp && $ppmp->items->isNotEmpty())
            <div class="mt-4 max-h-80 overflow-y-auto rounded-lg border border-gray-100 dark:border-gray-700">
                <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700">
                    <thead class="sticky top-0 bg-gray-50 dark:bg-gray-900/80">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-gray-500">Item</th>
                            <th class="px-3 py-2 text-right text-xs font-semibold uppercase text-gray-500">Total qty</th>
                            <th class="px-3 py-2 text-right text-xs font-semibold uppercase text-gray-500">Unit cost</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 dark:divide-gray-700">
                        @foreach ($ppmp->items->take(50) as $item)
                            <tr>
                                <td class="px-3 py-2 text-sm text-gray-900 dark:text-white">
                                    {{ $item->appItem?->item_name }}
                                    <span class="block text-xs text-gray-500">{{ $item->appItem?->item_code }}</span>
                                </td>
                                <td class="px-3 py-2 text-right text-sm text-gray-700 dark:text-gray-300">{{ $item->total_quantity }}</td>
                                <td class="px-3 py-2 text-right text-sm text-gray-700 dark:text-gray-300">₱{{ number_format((float) $item->estimated_unit_cost, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($ppmp->items->count() > 50)
                <p class="mt-2 text-xs text-gray-500">Showing first 50 of {{ $ppmp->items->count() }} items.</p>
            @endif
        @endif

        @if ($departmentBudget)
            <p class="mt-4 text-xs text-gray-500 dark:text-gray-400">
                Department budget available: ₱{{ number_format($departmentBudget->getAvailableBudget(), 2) }}
                (allocated ₱{{ number_format((float) $departmentBudget->allocated_budget, 2) }})
            </p>
        @endif
    </div>
</div>
