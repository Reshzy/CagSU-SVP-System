<div class="space-y-6">
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="min-w-[16rem] flex-1">
                <label for="budgetSearch" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-400">Search departments</label>
                <input
                    id="budgetSearch"
                    type="search"
                    wire:model.live.debounce.300ms="budgetSearch"
                    placeholder="Name or code"
                    class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-cagsu-maroon focus:ring-cagsu-maroon dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                />
            </div>
            <div class="flex flex-wrap gap-2">
                <button
                    type="button"
                    wire:click="quickSetBudget(1000000)"
                    wire:confirm="Set selected department allocated budget to ₱1,000,000?"
                    class="rounded-lg bg-cagsu-maroon px-3 py-2 text-xs font-semibold text-white hover:bg-cagsu-orange"
                >
                    Set selected to ₱1M
                </button>
                <button
                    type="button"
                    wire:click="$set('confirmClearReserved', true)"
                    class="rounded-lg border border-amber-300 px-3 py-2 text-xs font-semibold text-amber-800 hover:bg-amber-50 dark:border-amber-700 dark:text-amber-200 dark:hover:bg-amber-950/40"
                >
                    Clear reserved (dev)
                </button>
            </div>
        </div>

        @if ($confirmClearReserved)
            <div class="mt-4 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 dark:border-amber-700 dark:bg-amber-950/30">
                <p class="text-sm text-amber-900 dark:text-amber-200">
                    Clear reserved budget for the selected department? This does not cancel PRs — testing only.
                </p>
                <div class="mt-3 flex gap-2">
                    <button type="button" wire:click="clearReservedBudget" class="rounded-lg bg-amber-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-amber-800">
                        Confirm clear
                    </button>
                    <button type="button" wire:click="$set('confirmClearReserved', false)" class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-700 dark:border-gray-600 dark:text-gray-200">
                        Cancel
                    </button>
                </div>
            </div>
        @endif
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Department</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Allocated</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Reserved</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Utilized</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Available</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($budgetRows as $row)
                        <tr @class(['bg-amber-50/40 dark:bg-amber-950/10' => $departmentId === $row->department->id])>
                            <td class="px-4 py-3 text-sm">
                                <div class="font-medium text-gray-900 dark:text-white">{{ $row->department->name }}</div>
                                <div class="text-xs text-gray-500">{{ $row->department->code }}</div>
                            </td>
                            @if ($editingBudgetDepartmentId === $row->department->id)
                                <td colspan="4" class="px-4 py-3">
                                    <div class="flex flex-wrap items-end gap-3">
                                        <div>
                                            <label class="mb-1 block text-xs text-gray-500">Allocated</label>
                                            <input type="number" min="0" step="0.01" wire:model="editingAllocatedBudget" class="w-40 rounded-lg border-gray-300 text-sm focus:border-cagsu-maroon focus:ring-cagsu-maroon dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
                                        </div>
                                        <div class="min-w-[12rem] flex-1">
                                            <label class="mb-1 block text-xs text-gray-500">Notes</label>
                                            <input type="text" wire:model="editingBudgetNotes" class="w-full rounded-lg border-gray-300 text-sm focus:border-cagsu-maroon focus:ring-cagsu-maroon dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
                                        </div>
                                        <button type="button" wire:click="saveBudget" class="rounded-lg bg-cagsu-maroon px-3 py-2 text-xs font-semibold text-white hover:bg-cagsu-orange">Save</button>
                                        <button type="button" wire:click="cancelEditBudget" class="rounded-lg border border-gray-300 px-3 py-2 text-xs font-semibold text-gray-700 dark:border-gray-600 dark:text-gray-200">Cancel</button>
                                    </div>
                                    @error('editingAllocatedBudget') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </td>
                                <td></td>
                            @else
                                <td class="px-4 py-3 text-right text-sm text-gray-900 dark:text-white">₱{{ number_format($row->allocated, 2) }}</td>
                                <td class="px-4 py-3 text-right text-sm text-gray-700 dark:text-gray-300">₱{{ number_format($row->reserved, 2) }}</td>
                                <td class="px-4 py-3 text-right text-sm text-gray-700 dark:text-gray-300">₱{{ number_format($row->utilized, 2) }}</td>
                                <td class="px-4 py-3 text-right text-sm font-medium text-gray-900 dark:text-white">₱{{ number_format($row->available, 2) }}</td>
                                <td class="px-4 py-3 text-right">
                                    <button type="button" wire:click="startEditBudget({{ $row->department->id }})" class="text-xs font-semibold text-cagsu-maroon hover:text-cagsu-orange">
                                        Edit
                                    </button>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500">No departments found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
