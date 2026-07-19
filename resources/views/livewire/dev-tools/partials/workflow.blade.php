<div class="space-y-6">
    <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 dark:border-amber-800 dark:bg-amber-950/30">
        <p class="text-sm text-amber-900 dark:text-amber-200">
            Status jumps skip intermediate approvals. Jumping to BAC (or later) auto-sets Small Value Procurement, generates a resolution number/document, and creates a CEO approval record so BAC pages do not 500.
        </p>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-5">
        <div class="space-y-4 lg:col-span-3">
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div>
                        <label for="workflowSearch" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-400">Search</label>
                        <input
                            id="workflowSearch"
                            type="search"
                            wire:model.live.debounce.300ms="workflowSearch"
                            placeholder="PR number or purpose"
                            class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-cagsu-maroon focus:ring-cagsu-maroon dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        />
                    </div>
                    <div>
                        <label for="workflowStatusFilter" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-400">Status filter</label>
                        <select
                            id="workflowStatusFilter"
                            wire:model.live="workflowStatusFilter"
                            class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-cagsu-maroon focus:ring-cagsu-maroon dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        >
                            <option value="">All statuses</option>
                            @foreach ($statuses as $status)
                                <option value="{{ $status }}">{{ str_replace('_', ' ', $status) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($workflowPrs as $pr)
                        <li>
                            <button
                                type="button"
                                wire:click="selectPurchaseRequest({{ $pr->id }})"
                                @class([
                                    'flex w-full items-start justify-between gap-3 px-4 py-3 text-left transition hover:bg-gray-50 dark:hover:bg-gray-900/40',
                                    'bg-amber-50/60 ring-1 ring-inset ring-cagsu-maroon/30 dark:bg-gray-900' => $selectedPrId === $pr->id,
                                ])
                            >
                                <span>
                                    <span class="block text-sm font-semibold text-gray-900 dark:text-white">{{ $pr->pr_number }}</span>
                                    <span class="mt-0.5 block text-xs text-gray-500">{{ $pr->purpose }}</span>
                                    <span class="mt-1 block text-xs text-gray-400">{{ $pr->requester?->name }} · {{ $pr->department?->code }}</span>
                                </span>
                                <span class="shrink-0 rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-200">
                                    {{ str_replace('_', ' ', $pr->status) }}
                                </span>
                            </button>
                        </li>
                    @empty
                        <li class="px-4 py-8 text-center text-sm text-gray-500">No purchase requests match.</li>
                    @endforelse
                </ul>
                @if ($workflowPrs->hasPages())
                    <div class="border-t border-gray-100 px-4 py-3 dark:border-gray-700">
                        {{ $workflowPrs->links() }}
                    </div>
                @endif
            </div>
        </div>

        <div class="lg:col-span-2">
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-700 dark:text-gray-300">Status controls</h3>

                @if ($selectedPr)
                    <div class="mt-4 space-y-4">
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $selectedPr->pr_number }}</p>
                            <p class="text-xs text-gray-500">Current: {{ str_replace('_', ' ', $selectedPr->status) }}</p>
                            <a href="{{ route('purchase-requests.show', $selectedPr) }}" class="mt-1 inline-block text-xs font-semibold text-cagsu-maroon hover:text-cagsu-orange">
                                Open PR →
                            </a>
                        </div>

                        <div>
                            <label for="workflowTargetStatus" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-400">Set status</label>
                            <select
                                id="workflowTargetStatus"
                                wire:model="workflowTargetStatus"
                                class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-cagsu-maroon focus:ring-cagsu-maroon dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            >
                                @foreach ($statuses as $status)
                                    <option value="{{ $status }}">{{ str_replace('_', ' ', $status) }}</option>
                                @endforeach
                            </select>
                            @error('workflowTargetStatus') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <button
                            type="button"
                            wire:click="applyWorkflowStatus"
                            wire:confirm="Force-set this PR status? Intermediate approvals will be skipped."
                            class="w-full rounded-lg bg-cagsu-maroon px-4 py-2 text-sm font-semibold text-white hover:bg-cagsu-orange"
                        >
                            Apply status
                        </button>

                        <div>
                            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-400">Jump presets</p>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($jumpPresets as $preset => $status)
                                    <button
                                        type="button"
                                        wire:click="jumpPreset('{{ $preset }}')"
                                        wire:confirm="Jump to {{ str_replace('_', ' ', $status) }}?"
                                        class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-semibold capitalize text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700"
                                    >
                                        {{ $preset }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @else
                    <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">Select a purchase request from the list.</p>
                @endif

                @error('selectedPrId') <p class="mt-3 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>
    </div>
</div>
