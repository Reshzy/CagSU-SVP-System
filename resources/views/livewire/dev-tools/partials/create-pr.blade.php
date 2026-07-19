<div class="space-y-6">
    @if ($createdPrNumber)
        <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 dark:border-green-800 dark:bg-green-950/30">
            <p class="text-sm font-medium text-green-900 dark:text-green-200">
                Created <span class="font-bold">{{ $createdPrNumber }}</span>
                @if ($createdPrId)
                    —
                    <a href="{{ route('purchase-requests.show', $createdPrId) }}" class="underline hover:text-cagsu-orange">
                        View purchase request
                    </a>
                @endif
            </p>
        </div>
    @endif

    {{-- Steps --}}
    <div class="flex flex-wrap gap-2">
        @foreach ([1 => 'Who / where', 2 => 'Details', 3 => 'Items', 4 => 'Confirm'] as $step => $label)
            <button
                type="button"
                wire:click="$set('createStep', {{ $step }})"
                @class([
                    'rounded-full px-3 py-1 text-xs font-semibold transition',
                    'bg-cagsu-maroon text-white' => $createStep === $step,
                    'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300' => $createStep !== $step,
                ])
            >
                {{ $step }}. {{ $label }}
            </button>
        @endforeach
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        @if ($createStep === 1)
            <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-700 dark:text-gray-300">Who & where</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Department comes from the context bar. Choose the requester who “owns” the PR.</p>

            <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-400">Department</label>
                    <p class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-800 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200">
                        {{ $departments->firstWhere('id', $departmentId)?->name ?? 'Select a department above' }}
                    </p>
                    @error('departmentId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="requesterId" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-400">Requester</label>
                    <select
                        id="requesterId"
                        wire:model="requesterId"
                        class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-cagsu-maroon focus:ring-cagsu-maroon dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                    >
                        <option value="">Select requester…</option>
                        @foreach ($requesters as $requester)
                            <option value="{{ $requester->id }}">{{ $requester->name }} ({{ $requester->email }})</option>
                        @endforeach
                    </select>
                    @error('requesterId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    @if ($requesters->isEmpty() && $departmentId)
                        <p class="mt-1 text-xs text-amber-600">No End User / Dean users in this department.</p>
                    @endif
                </div>
            </div>
        @elseif ($createStep === 2)
            <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-700 dark:text-gray-300">PR details</h3>
            <div class="mt-6 space-y-4">
                <div>
                    <label for="purpose" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-400">Purpose</label>
                    <input id="purpose" type="text" wire:model="purpose" class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-cagsu-maroon focus:ring-cagsu-maroon dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
                    @error('purpose') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="justification" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-400">Justification</label>
                    <textarea id="justification" rows="3" wire:model="justification" class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-cagsu-maroon focus:ring-cagsu-maroon dark:border-gray-600 dark:bg-gray-700 dark:text-white"></textarea>
                    @error('justification') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="dateNeeded" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-400">Date needed (optional)</label>
                        <input id="dateNeeded" type="date" wire:model="dateNeeded" class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-cagsu-maroon focus:ring-cagsu-maroon dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
                    </div>
                    <div>
                        <label for="landingStatus" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-400">Landing status</label>
                        <select id="landingStatus" wire:model="landingStatus" class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-cagsu-maroon focus:ring-cagsu-maroon dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            @foreach ($statuses as $status)
                                <option value="{{ $status }}">{{ str_replace('_', ' ', $status) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                    <input type="checkbox" wire:model="notifyOfficers" class="rounded border-gray-300 text-cagsu-maroon focus:ring-cagsu-maroon" />
                    Notify Supply Officers (default off)
                </label>
            </div>
        @elseif ($createStep === 3)
            <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-700 dark:text-gray-300">Items</h3>
            <div class="mt-4 flex gap-2">
                <button type="button" wire:click="$set('itemMode', 'ppmp')" @class(['rounded-lg px-3 py-1.5 text-xs font-semibold', 'bg-cagsu-maroon text-white' => $itemMode === 'ppmp', 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200' => $itemMode !== 'ppmp'])>
                    Quick-seed from PPMP
                </button>
                <button type="button" wire:click="$set('itemMode', 'manual')" @class(['rounded-lg px-3 py-1.5 text-xs font-semibold', 'bg-cagsu-maroon text-white' => $itemMode === 'manual', 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200' => $itemMode !== 'manual'])>
                    Manual rows
                </button>
            </div>

            @error('items') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror

            <div class="mt-4 rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/40">
                <label class="flex items-center gap-2 text-sm font-medium text-gray-800 dark:text-gray-200">
                    <input type="checkbox" wire:model.live="groupAsLot" class="rounded border-gray-300 text-cagsu-maroon focus:ring-cagsu-maroon" />
                    Group items into a lot
                </label>
                @if ($groupAsLot)
                    <div class="mt-3">
                        <label for="lotName" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-400">Lot name</label>
                        <input
                            id="lotName"
                            type="text"
                            wire:model="lotName"
                            placeholder="e.g. Office Furniture Lot"
                            class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-cagsu-maroon focus:ring-cagsu-maroon dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        />
                        @error('lotName') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Creates one lot header plus child line items (same structure as the end-user PR form).
                        </p>
                    </div>
                @endif
            </div>

            @if ($itemMode === 'ppmp')
                @if (! $ppmp || $ppmp->status !== 'validated')
                    <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-950/30 dark:text-amber-200">
                        A validated PPMP is required for quick-seed.
                        <button type="button" wire:click="setTab('ppmp')" class="font-semibold underline">Go to PPMP tab</button>
                    </div>
                @else
                    <div class="mt-4 max-h-96 space-y-2 overflow-y-auto">
                        @foreach ($ppmp->items as $ppmpItem)
                            @php $selected = in_array($ppmpItem->id, $selectedPpmpItemIds, true); @endphp
                            <div @class(['rounded-lg border p-3', 'border-cagsu-maroon bg-amber-50/50 dark:bg-gray-900' => $selected, 'border-gray-200 dark:border-gray-700' => ! $selected])>
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <label class="flex min-w-0 flex-1 cursor-pointer items-start gap-2">
                                        <input
                                            type="checkbox"
                                            wire:click="togglePpmpItem({{ $ppmpItem->id }})"
                                            @checked($selected)
                                            class="mt-1 rounded border-gray-300 text-cagsu-maroon focus:ring-cagsu-maroon"
                                        />
                                        <span>
                                            <span class="block text-sm font-medium text-gray-900 dark:text-white">{{ $ppmpItem->appItem?->item_name }}</span>
                                            <span class="block text-xs text-gray-500">{{ $ppmpItem->appItem?->item_code }} · {{ $ppmpItem->appItem?->unit_of_measure }}</span>
                                        </span>
                                    </label>
                                    @if ($selected)
                                        <div class="flex gap-2">
                                            <input type="number" min="1" wire:model="ppmpItemOverrides.{{ $ppmpItem->id }}.quantity" class="w-20 rounded-lg border-gray-300 text-sm focus:border-cagsu-maroon focus:ring-cagsu-maroon dark:border-gray-600 dark:bg-gray-700 dark:text-white" placeholder="Qty" />
                                            <input type="number" min="0.01" step="0.01" wire:model="ppmpItemOverrides.{{ $ppmpItem->id }}.unit_cost" class="w-28 rounded-lg border-gray-300 text-sm focus:border-cagsu-maroon focus:ring-cagsu-maroon dark:border-gray-600 dark:bg-gray-700 dark:text-white" placeholder="Cost" />
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            @else
                <div class="mt-4 space-y-3">
                    @foreach ($manualItems as $index => $item)
                        <div class="grid grid-cols-1 gap-3 rounded-lg border border-gray-200 p-3 dark:border-gray-700 sm:grid-cols-6">
                            <div class="sm:col-span-2">
                                <input type="text" wire:model="manualItems.{{ $index }}.item_name" placeholder="Item name" class="block w-full rounded-lg border-gray-300 text-sm focus:border-cagsu-maroon focus:ring-cagsu-maroon dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
                                @error("manualItems.$index.item_name") <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <input type="text" wire:model="manualItems.{{ $index }}.unit_of_measure" placeholder="UOM" class="block w-full rounded-lg border-gray-300 text-sm focus:border-cagsu-maroon focus:ring-cagsu-maroon dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
                            </div>
                            <div>
                                <input type="number" min="1" wire:model="manualItems.{{ $index }}.quantity_requested" placeholder="Qty" class="block w-full rounded-lg border-gray-300 text-sm focus:border-cagsu-maroon focus:ring-cagsu-maroon dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
                            </div>
                            <div>
                                <input type="number" min="0.01" step="0.01" wire:model="manualItems.{{ $index }}.estimated_unit_cost" placeholder="Unit cost" class="block w-full rounded-lg border-gray-300 text-sm focus:border-cagsu-maroon focus:ring-cagsu-maroon dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
                            </div>
                            <div class="flex items-center">
                                <button type="button" wire:click="removeManualItem({{ $index }})" class="text-xs font-semibold text-red-600 hover:text-red-700">Remove</button>
                            </div>
                        </div>
                    @endforeach
                    <button type="button" wire:click="addManualItem" class="text-sm font-semibold text-cagsu-maroon hover:text-cagsu-orange">+ Add row</button>
                </div>
            @endif
        @else
            <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-700 dark:text-gray-300">Confirm</h3>
            <dl class="mt-4 grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                <div>
                    <dt class="text-xs uppercase tracking-wide text-gray-500">Department</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $departments->firstWhere('id', $departmentId)?->name }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-gray-500">Requester</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $requesters->firstWhere('id', $requesterId)?->name }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs uppercase tracking-wide text-gray-500">Purpose</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ $purpose }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-gray-500">Landing status</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">{{ str_replace('_', ' ', $landingStatus) }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-gray-500">Items</dt>
                    <dd class="font-medium text-gray-900 dark:text-white">
                        {{ $itemMode === 'ppmp' ? count($selectedPpmpItemIds).' PPMP item(s)' : count($manualItems).' manual row(s)' }}
                        @if ($groupAsLot)
                            <span class="text-cagsu-maroon">· Lot: {{ $lotName ?: '(unnamed)' }}</span>
                        @endif
                    </dd>
                </div>
            </dl>
            @error('budget') <p class="mt-3 text-sm text-red-600">{{ $message }}</p> @enderror
            @error('items') <p class="mt-3 text-sm text-red-600">{{ $message }}</p> @enderror
        @endif

        <div class="mt-6 flex flex-wrap items-center justify-between gap-3 border-t border-gray-100 pt-4 dark:border-gray-700">
            <button
                type="button"
                wire:click="previousCreateStep"
                @disabled($createStep === 1)
                class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 disabled:opacity-40 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700"
            >
                Back
            </button>
            @if ($createStep < 4)
                <button type="button" wire:click="nextCreateStep" class="rounded-lg bg-cagsu-maroon px-4 py-2 text-sm font-semibold text-white hover:bg-cagsu-orange">
                    Continue
                </button>
            @else
                <button type="button" wire:click="createPurchaseRequest" wire:loading.attr="disabled" class="rounded-lg bg-cagsu-maroon px-4 py-2 text-sm font-semibold text-white hover:bg-cagsu-orange disabled:opacity-60">
                    <span wire:loading.remove wire:target="createPurchaseRequest">Create purchase request</span>
                    <span wire:loading wire:target="createPurchaseRequest">Creating…</span>
                </button>
            @endif
        </div>
    </div>
</div>
