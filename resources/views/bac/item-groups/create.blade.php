@section('title', 'BAC - Split PR Items into Groups')

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-gray-800 leading-tight">{{ __('Split Items into Groups - ') . $purchaseRequest->pr_number }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="mb-6">
                        <p class="text-gray-700">
                            Group similar items together (e.g., separate food items from appliances, office supplies from IT equipment).
                            Each group will have its own RFQ, quotations, AOQ, and Purchase Order.
                        </p>
                    </div>

                    @if($purchaseRequest->itemGroups->count() > 0)
                        <div class="mb-4 p-4 bg-yellow-50 border border-yellow-300 rounded">
                            <p class="text-yellow-800">
                                <strong>Note:</strong> This PR already has groups. Creating new groups will replace the existing ones.
                                <a href="{{ route('bac.item-groups.edit', $purchaseRequest) }}" class="underline">Edit existing groups instead</a>.
                            </p>
                        </div>
                    @endif

                    <form action="{{ route('bac.item-groups.store', $purchaseRequest) }}" method="POST" id="groupingForm">
                        @csrf

                        <div id="groupsContainer">
                            <div class="group-card mb-6 border border-gray-300 rounded-lg p-4" data-group-index="0">
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="text-lg font-semibold text-gray-800">Group 1</h3>
                                    <button type="button" class="remove-group-btn text-red-600 hover:text-red-800 hidden">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>

                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Group Name</label>
                                    <input type="text" name="groups[0][name]" class="block w-full border-gray-300 rounded-md shadow-sm focus:border-cagsu-maroon focus:ring focus:ring-cagsu-maroon focus:ring-opacity-50" placeholder="e.g., Office Supplies, IT Equipment" required>
                                    @error('groups.0.name')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Items in this Group</label>
                                    <div class="space-y-2 max-h-96 overflow-y-auto border border-gray-200 rounded p-3 bg-gray-50">
                                        @foreach($purchaseRequest->items as $item)
                                            <label class="flex items-start p-2 hover:bg-white rounded cursor-pointer">
                                                <input type="checkbox" name="groups[0][items][]" value="{{ $item->id }}" class="mt-1 mr-3 item-checkbox" data-item-id="{{ $item->id }}">
                                                <div class="flex-1">
                                                    <div class="font-medium text-gray-900">{{ $item->item_name }}</div>
                                                    <div class="text-sm text-gray-600">
                                                        Qty: {{ $item->quantity_requested }} {{ $item->unit_of_measure }} | 
                                                        ABC: ₱{{ number_format((float)$item->estimated_unit_cost, 2) }}
                                                    </div>
                                                </div>
                                            </label>
                                        @endforeach
                                    </div>
                                    @error('groups.0.items')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
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
                                Save Groups
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        let groupIndex = 1;
        const items = @json($purchaseRequest->items);

        document.getElementById('addGroupBtn').addEventListener('click', function() {
            const container = document.getElementById('groupsContainer');
            const newGroup = document.createElement('div');
            newGroup.className = 'group-card mb-6 border border-gray-300 rounded-lg p-4';
            newGroup.dataset.groupIndex = groupIndex;

            let itemsHtml = '';
            items.forEach(item => {
                itemsHtml += `
                    <label class="flex items-start p-2 hover:bg-white rounded cursor-pointer">
                        <input type="checkbox" name="groups[${groupIndex}][items][]" value="${item.id}" class="mt-1 mr-3 item-checkbox" data-item-id="${item.id}">
                        <div class="flex-1">
                            <div class="font-medium text-gray-900">${item.item_name}</div>
                            <div class="text-sm text-gray-600">
                                Qty: ${item.quantity_requested} ${item.unit_of_measure} | 
                                ABC: ₱${parseFloat(item.estimated_unit_cost).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',')}
                            </div>
                        </div>
                    </label>
                `;
            });

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
            groups.forEach((group, index) => {
                const removeBtn = group.querySelector('.remove-group-btn');
                if (groups.length > 1) {
                    removeBtn.classList.remove('hidden');
                } else {
                    removeBtn.classList.add('hidden');
                }
            });
        }

        // Warn about items in multiple groups
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

            // Check if all items are assigned
            const totalItems = {{ $purchaseRequest->items->count() }};
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
