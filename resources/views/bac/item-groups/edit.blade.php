@section('title', 'BAC - Edit Item Groups')

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-gray-800 leading-tight">{{ __('Edit Item Groups - ') . $purchaseRequest->pr_number }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="mb-6">
                        <p class="text-gray-700">
                            Modify the item groups. Each group will have its own RFQ, quotations, AOQ, and Purchase Order.
                            Lots are selected as a single unit — expand a lot to see its individual items.
                        </p>
                    </div>

                    <form action="{{ route('bac.item-groups.update', $purchaseRequest) }}" method="POST" id="groupingForm">
                        @csrf
                        @method('PUT')

                        <div id="groupsContainer">
                            @foreach($purchaseRequest->itemGroups as $index => $group)
                                <div class="group-card mb-6 border border-gray-300 rounded-lg p-4" data-group-index="{{ $index }}">
                                    <div class="flex justify-between items-center mb-4">
                                        <h3 class="text-lg font-semibold text-gray-800">{{ $group->group_name }}</h3>
                                        <button type="button" class="remove-group-btn text-red-600 hover:text-red-800 {{ $purchaseRequest->itemGroups->count() <= 1 ? 'hidden' : '' }}">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>

                                    <div class="mb-4">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Group Name</label>
                                        <input type="text" name="groups[{{ $index }}][name]" value="{{ $group->group_name }}" class="block w-full border-gray-300 rounded-md shadow-sm focus:border-cagsu-maroon focus:ring focus:ring-cagsu-maroon focus:ring-opacity-50" placeholder="e.g., Office Supplies, IT Equipment" required>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Items in this Group</label>
                                        <div class="space-y-2 max-h-96 overflow-y-auto border border-gray-200 rounded p-3 bg-gray-50">
                                            @foreach($quotableItems as $item)
                                                @include('bac.item-groups.partials.quotable-item-checkbox', [
                                                    'item' => $item,
                                                    'inputName' => 'groups['.$index.'][items][]',
                                                    'checked' => $group->items->contains('id', $item->id),
                                                ])
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="flex gap-4 mb-6">
                            <button type="button" id="addGroupBtn" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                                + Add Another Group
                            </button>
                        </div>

                        <div class="flex justify-between items-center pt-4 border-t border-gray-200">
                            <a href="{{ route('bac.quotations.manage', $purchaseRequest) }}" class="text-gray-600 hover:text-gray-800">
                                Cancel
                            </a>
                            <button type="submit" class="px-6 py-2 bg-cagsu-maroon text-white rounded-md hover:bg-red-800">
                                Update Groups
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        let groupIndex = {{ $purchaseRequest->itemGroups->count() }};
        const items = @json($quotableItemsPayload);

        function formatMoney(value) {
            return parseFloat(value).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        }

        function escapeHtml(text) {
            return String(text)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }

        function buildItemCheckboxHtml(item, nameAttr) {
            const children = item.lot_children || [];
            let childrenHtml = '';

            if (item.is_lot && children.length > 0) {
                childrenHtml = `
                    <div x-show="open" x-cloak class="mt-2 ml-8 space-y-1 border-l-2 border-indigo-100 pl-3">
                        ${children.map(child => `
                            <div class="text-sm text-gray-700">
                                <span class="font-medium">${escapeHtml(child.item_name)}</span>
                                <span class="text-gray-500">
                                    — Qty: ${child.quantity_requested} ${escapeHtml(child.unit_of_measure)} |
                                    ABC: ₱${formatMoney(child.estimated_unit_cost)}
                                </span>
                            </div>
                        `).join('')}
                    </div>
                `;
            }

            const expandButton = item.is_lot && children.length > 0
                ? `
                    <button
                        type="button"
                        class="ml-2 mt-1 inline-flex items-center gap-1 text-xs font-medium text-blue-700 hover:text-blue-900 shrink-0"
                        @click="open = !open"
                        :aria-expanded="open.toString()"
                    >
                        <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-90': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                        <span x-text="open ? 'Hide items' : 'Show items'"></span>
                    </button>
                `
                : '';

            const lotCount = item.is_lot
                ? `<span class="text-gray-500">(${children.length} items)</span>`
                : '';

            return `
                <div class="p-2 hover:bg-white rounded" ${item.is_lot ? 'x-data="{ open: false }"' : ''}>
                    <div class="flex items-start">
                        <label class="flex items-start flex-1 cursor-pointer min-w-0">
                            <input type="checkbox" name="${nameAttr}" value="${item.id}" class="mt-1 mr-3 item-checkbox" data-item-id="${item.id}">
                            <div class="flex-1 min-w-0">
                                <div class="font-medium text-gray-900">${escapeHtml(item.item_name)}</div>
                                <div class="text-sm text-gray-600">
                                    Qty: ${item.quantity_requested} ${escapeHtml(item.unit_of_measure)} |
                                    ABC: ₱${formatMoney(item.estimated_total_cost)}
                                    ${lotCount}
                                </div>
                            </div>
                        </label>
                        ${expandButton}
                    </div>
                    ${childrenHtml}
                </div>
            `;
        }

        document.getElementById('addGroupBtn').addEventListener('click', function() {
            const container = document.getElementById('groupsContainer');
            const newGroup = document.createElement('div');
            newGroup.className = 'group-card mb-6 border border-gray-300 rounded-lg p-4';
            newGroup.dataset.groupIndex = groupIndex;

            const itemsHtml = items.map(item => buildItemCheckboxHtml(item, `groups[${groupIndex}][items][]`)).join('');

            newGroup.innerHTML = `
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Group ${groupIndex + 1}</h3>
                    <button type="button" class="remove-group-btn text-red-600 hover:text-red-800">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Group Name</label>
                    <input type="text" name="groups[${groupIndex}][name]" class="block w-full border-gray-300 rounded-md shadow-sm focus:border-cagsu-maroon focus:ring focus:ring-cagsu-maroon focus:ring-opacity-50" placeholder="e.g., Office Supplies, IT Equipment" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Items in this Group</label>
                    <div class="space-y-2 max-h-96 overflow-y-auto border border-gray-200 rounded p-3 bg-gray-50">
                        ${itemsHtml}
                    </div>
                </div>
            `;

            container.appendChild(newGroup);
            groupIndex++;
            updateRemoveButtons();
        });

        document.addEventListener('click', function(e) {
            if (e.target.closest('.remove-group-btn')) {
                e.target.closest('.group-card').remove();
                updateRemoveButtons();
            }
        });

        function updateRemoveButtons() {
            const groups = document.querySelectorAll('.group-card');
            groups.forEach((group) => {
                const removeBtn = group.querySelector('.remove-group-btn');
                if (groups.length > 1) {
                    removeBtn.classList.remove('hidden');
                } else {
                    removeBtn.classList.add('hidden');
                }
            });
        }

        document.getElementById('groupingForm').addEventListener('submit', function(e) {
            const checkedItems = {};
            const checkboxes = document.querySelectorAll('.item-checkbox:checked');
            let duplicates = [];

            checkboxes.forEach(cb => {
                const itemId = cb.dataset.itemId;
                if (checkedItems[itemId]) {
                    duplicates.push(itemId);
                } else {
                    checkedItems[itemId] = true;
                }
            });

            if (duplicates.length > 0) {
                e.preventDefault();
                alert('Some items are selected in multiple groups. Each item can only belong to one group.');
                return false;
            }

            const totalItems = {{ $quotableItems->count() }};
            const assignedItems = Object.keys(checkedItems).length;

            if (assignedItems < totalItems) {
                if (!confirm(`Warning: Only ${assignedItems} out of ${totalItems} items are assigned to groups. Unassigned items will not be included in any RFQ. Continue?`)) {
                    e.preventDefault();
                    return false;
                }
            }
        });
    </script>
    @endpush
</x-app-layout>
