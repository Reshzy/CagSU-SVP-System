<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-black leading-tight">
            {{ __('Create Replacement Purchase Request') }} - FY {{ $fiscalYear }}
        </h2>
    </x-slot>

    <div class="py-12" x-data="prManager()">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Original PR Info Banner -->
            <div class="mb-6 bg-yellow-50 dark:bg-yellow-900 border border-yellow-400 dark:border-yellow-700 text-yellow-800 dark:text-yellow-200 px-4 py-3 rounded relative">
                <strong class="font-bold">Replacing Returned PR:</strong>
                <p class="mt-1">
                    PR Number: <strong>{{ $originalPr->pr_number }}</strong><br>
                    Return Reason: {{ $originalPr->return_reason ?? 'Not specified' }}<br>
                    Returned By: {{ $originalPr->returnedBy->name ?? 'Unknown' }} on {{ $originalPr->returned_at?->format('M d, Y') }}
                </p>
            </div>

            @if ($errors->any())
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <strong class="font-bold">Error!</strong>
                    <ul class="mt-2 list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="flex gap-6">
                <!-- Main Content Area -->
                <div class="flex-1">
                    <!-- Budget Status -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                        <div class="p-6">
                            <div class="flex justify-between items-center">
                                <h3 class="text-lg font-bold text-gray-800 dark:text-gray-200">Budget Available</h3>
                                <div class="text-2xl font-bold text-white">₱<span x-text="formatNumber(budgetAvailable)">{{ number_format($departmentBudget->getAvailableBudget(), 2) }}</span></div>
                            </div>
                            <div class="mt-2 flex justify-between items-center">
                                <span class="text-sm text-gray-600 dark:text-gray-400">PR Total: ₱<span x-text="formatNumber(prTotal)">0.00</span></span>
                                <span class="text-sm" :class="budgetRemaining >= 0 ? 'text-green-600' : 'text-red-600'">
                                    Remaining: ₱<span x-text="formatNumber(budgetRemaining)">{{ number_format($departmentBudget->getAvailableBudget(), 2) }}</span>
                                </span>
                            </div>
                            <div class="mt-2 w-full bg-gray-200 rounded-full h-2">
                                <div class="h-2 rounded-full transition-all duration-300" 
                                     :class="budgetRemaining >= 0 ? 'bg-blue-600' : 'bg-red-600'"
                                     :style="`width: ${Math.min(100, (prTotal / budgetAvailable) * 100)}%`">
                                </div>
                            </div>
                        </div>
                    </div>

                    <form action="{{ route('purchase-requests.replacement.store', $originalPr) }}" method="POST" enctype="multipart/form-data" id="prForm" @submit="prepareSubmit">
                        @csrf
                        
                        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="text-lg font-bold text-gray-800 dark:text-gray-200">Select Items from PPMP</h3>
                                    <div class="text-sm text-gray-600 dark:text-gray-400">
                                        <span x-text="selectedCount"></span> items selected
                                    </div>
                                </div>
                                
                                <!-- Search Box -->
                                <div class="mb-6">
                                    <input 
                                        type="text" 
                                        x-model="searchQuery"
                                        placeholder="Search items by name, code, or category..."
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    />
                                </div>

                                <!-- Categories Accordion -->
                                @php
                                    $mainCategories = [];
                                    $part2Categories = [];
                                    foreach ($ppmpItems as $category => $items) {
                                        $upper = strtoupper($category);
                                        if (strpos($upper, 'PART II') !== false || strpos($upper, 'PART 2') !== false || strpos($upper, 'OTHER ITEMS') !== false) {
                                            $part2Categories[$category] = $items;
                                        } else {
                                            $mainCategories[$category] = $items;
                                        }
                                    }
                                @endphp

                                @foreach ($mainCategories as $category => $items)
                                    <div class="mb-4 border border-gray-200 dark:border-gray-700 rounded-lg"
                                         x-show="categoryVisible('{{ $category }}', {{ json_encode($items->pluck('appItem.item_name')->toArray()) }}, {{ json_encode($items->pluck('appItem.item_code')->toArray()) }})">
                                        <!-- Category Header -->
                                        <div class="bg-gray-50 dark:bg-gray-900 px-4 py-3 cursor-pointer flex justify-between items-center"
                                             @click="toggleCategory('{{ $category }}')">
                                            <div class="flex items-center gap-2">
                                                <svg class="w-5 h-5 transition-transform duration-200" 
                                                     :class="{'rotate-90': expandedCategories.includes('{{ $category }}')}"
                                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                                </svg>
                                                <h4 class="text-md font-semibold text-gray-800 dark:text-gray-200">{{ $category }}</h4>
                                                <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-blue-900 dark:text-blue-300">
                                                    {{ $items->count() }} items
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Category Items -->
                                        <div x-show="expandedCategories.includes('{{ $category }}')" 
                                             x-transition
                                             class="p-4 space-y-2">
                                            @foreach ($items as $item)
                                                @php
                                                    $isPriceEditable = str_contains(strtoupper($category), 'SOFTWARE') || 
                                                                       str_contains(strtoupper($category), 'PART II') || 
                                                                       str_contains(strtoupper($category), 'OTHER ITEMS');
                                                @endphp
                                                <div class="border border-gray-200 dark:border-gray-700 rounded p-3 hover:border-indigo-500 transition-colors"
                                                     x-show="itemVisible('{{ $item->appItem->item_name }}', '{{ $item->appItem->item_code }}', '{{ $category }}')">
                                                    <div class="flex justify-between items-start">
                                                        <div class="flex-1">
                                                            <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $item->appItem->item_name }}</div>
                                                            <div class="text-xs text-gray-500">{{ $item->appItem->item_code }}</div>
                                                            <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                                                {{ $item->appItem->unit_of_measure }}
                                                                @if($isPriceEditable)
                                                                    <span class="ml-2 px-2 py-0.5 bg-yellow-100 text-yellow-800 rounded text-xs">Custom Price</span>
                                                                @else
                                                                    <span class="ml-2 font-semibold">₱{{ number_format($item->estimated_unit_cost, 2) }}</span>
                                                                @endif
                                                            </div>
                                                        </div>
                                                        <button type="button"
                                                            @click="addItem({{ $item->id }}, {
                                                                id: {{ $item->id }},
                                                                name: '{{ addslashes($item->appItem->item_name) }}',
                                                                code: '{{ $item->appItem->item_code }}',
                                                                unit: '{{ $item->appItem->unit_of_measure }}',
                                                                price: {{ $item->estimated_unit_cost }},
                                                                specs: '{{ addslashes($item->appItem->specifications ?? '') }}',
                                                                isPriceEditable: {{ $isPriceEditable ? 'true' : 'false' }}
                                                            })"
                                                            :disabled="isSelected({{ $item->id }})"
                                                            class="px-3 py-1 bg-indigo-600 text-white text-xs rounded hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed">
                                                            <span x-show="!isSelected({{ $item->id }})">Add to PR</span>
                                                            <span x-show="isSelected({{ $item->id }})">Added</span>
                                                        </button>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach

                                @if(count($part2Categories) > 0)
                                    <div class="border-t pt-4 mt-4">
                                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Part 2 / Other Items</h4>
                                        @foreach($part2Categories as $category => $items)
                                            <div class="mb-4 border border-gray-200 dark:border-gray-700 rounded-lg"
                                                 x-show="categoryVisible('{{ $category }}', {{ json_encode($items->pluck('appItem.item_name')->toArray()) }}, {{ json_encode($items->pluck('appItem.item_code')->toArray()) }})">
                                                <div class="bg-gray-50 dark:bg-gray-900 px-4 py-3 cursor-pointer flex justify-between items-center"
                                                     @click="toggleCategory('{{ $category }}')">
                                                    <div class="flex items-center gap-2">
                                                        <svg class="w-5 h-5 transition-transform duration-200" 
                                                             :class="{'rotate-90': expandedCategories.includes('{{ $category }}')}"
                                                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                                        </svg>
                                                        <h4 class="text-md font-semibold text-gray-800 dark:text-gray-200">{{ $category }}</h4>
                                                        <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-blue-900 dark:text-blue-300">
                                                            {{ $items->count() }} items
                                                        </span>
                                                    </div>
                                                </div>

                                                <div x-show="expandedCategories.includes('{{ $category }}')" 
                                                     x-transition
                                                     class="p-4 space-y-2">
                                                    @foreach ($items as $item)
                                                        @php
                                                            $isPriceEditable = true;
                                                        @endphp
                                                        <div class="border border-gray-200 dark:border-gray-700 rounded p-3 hover:border-indigo-500 transition-colors"
                                                             x-show="itemVisible('{{ $item->appItem->item_name }}', '{{ $item->appItem->item_code }}', '{{ $category }}')">
                                                            <div class="flex justify-between items-start">
                                                                <div class="flex-1">
                                                                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $item->appItem->item_name }}</div>
                                                                    <div class="text-xs text-gray-500">{{ $item->appItem->item_code }}</div>
                                                                    <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                                                        {{ $item->appItem->unit_of_measure }}
                                                                        <span class="ml-2 px-2 py-0.5 bg-yellow-100 text-yellow-800 rounded text-xs">Custom Price</span>
                                                                    </div>
                                                                </div>
                                                                <button type="button"
                                                                    @click="addItem({{ $item->id }}, {
                                                                        id: {{ $item->id }},
                                                                        name: '{{ addslashes($item->appItem->item_name) }}',
                                                                        code: '{{ $item->appItem->item_code }}',
                                                                        unit: '{{ $item->appItem->unit_of_measure }}',
                                                                        price: {{ $item->estimated_unit_cost }},
                                                                        specs: '{{ addslashes($item->appItem->specifications ?? '') }}',
                                                                        isPriceEditable: true
                                                                    })"
                                                                    :disabled="isSelected({{ $item->id }})"
                                                                    class="px-3 py-1 bg-indigo-600 text-white text-xs rounded hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed">
                                                                    <span x-show="!isSelected({{ $item->id }})">Add to PR</span>
                                                                    <span x-show="isSelected({{ $item->id }})">Added</span>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Sticky Summary Sidebar -->
                <div class="w-96 sticky top-4 self-start space-y-6">
                    <!-- PR Details Form -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-4">
                            <h3 class="text-lg font-bold mb-4 text-gray-800 dark:text-gray-200">PR Details</h3>
                            
                            <div class="space-y-4">
                                <div>
                                    <label for="purpose" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Purpose <span class="text-red-500">*</span>
                                    </label>
                                    <input 
                                        type="text" 
                                        id="purpose" 
                                        name="purpose" 
                                        value="{{ old('purpose', $originalPr->purpose) }}"
                                        required
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        placeholder="Enter purpose of procurement"
                                    />
                                </div>

                                <div>
                                    <label for="justification" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Justification
                                    </label>
                                    <textarea 
                                        id="justification" 
                                        name="justification"
                                        rows="3"
                                        required
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        placeholder="Why is this procurement needed?"
                                    >{{ old('justification', $originalPr->justification) }}</textarea>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Attachments (Optional)
                                    </label>
                                    <input 
                                        type="file" 
                                        name="attachments[]" 
                                        multiple
                                        class="block w-full text-sm text-gray-500
                                            file:mr-4 file:py-2 file:px-4
                                            file:rounded-md file:border-0
                                            file:text-sm file:font-semibold
                                            file:bg-indigo-50 file:text-indigo-700
                                            hover:file:bg-indigo-100"
                                    />
                                    <p class="mt-1 text-xs text-gray-500">Max 10MB per file</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Selected Items Summary -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-4">
                            <h3 class="text-lg font-bold mb-4 text-gray-800 dark:text-gray-200">Selected Items</h3>
                            
                            <div class="mb-4 text-sm">
                                <div class="flex justify-between mb-2 text-gray-600 dark:text-gray-400">
                                    <span>Total Items:</span>
                                    <span class="font-semibold text-white" x-text="selectedCount">0</span>
                                </div>
                                <div class="flex justify-between mb-2 text-gray-600 dark:text-gray-400">
                                    <span>Total Cost:</span>
                                    <span class="font-semibold text-white">₱<span x-text="formatNumber(prTotal)">0.00</span></span>
                                </div>
                            </div>

                            <div class="max-h-96 overflow-y-auto space-y-2" x-show="selectedCount > 0">
                                <template x-for="item in selectedItems" :key="item.id">
                                    <div class="bg-gray-50 dark:bg-gray-900 p-3 rounded text-sm border border-gray-200 dark:border-gray-700">
                                        <div class="flex justify-between items-start mb-2">
                                            <div class="flex-1">
                                                <div class="font-semibold text-gray-800 dark:text-gray-200" x-text="item.name"></div>
                                                <div class="text-xs text-gray-500" x-text="item.code"></div>
                                            </div>
                                            <button 
                                                type="button"
                                                @click="removeItem(item.id)"
                                                class="text-red-500 hover:text-red-700 ml-2">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            </button>
                                        </div>
                                        
                                        <div class="space-y-2">
                                            <div class="flex items-center gap-2" x-show="item.isPriceEditable">
                                                <label class="text-xs text-gray-600 dark:text-gray-400 w-16">Price:</label>
                                                <div class="flex items-center flex-1">
                                                    <span class="text-xs mr-1">₱</span>
                                                    <input 
                                                        type="number" 
                                                        step="0.01" 
                                                        min="0"
                                                        :value="item.price"
                                                        @input="updatePrice(item.id, $event.target.value)"
                                                        class="w-full text-xs rounded border-gray-300"
                                                    />
                                                </div>
                                            </div>
                                            
                                            <div class="flex items-center gap-2" x-show="!item.isPriceEditable">
                                                <label class="text-xs text-gray-600 dark:text-gray-400 w-16">Price:</label>
                                                <span class="text-xs text-white">₱<span x-text="formatNumber(item.price)"></span></span>
                                            </div>

                                            <div class="flex items-center gap-2">
                                                <label class="text-xs text-gray-600 dark:text-gray-400 w-16">Quantity:</label>
                                                <input 
                                                    type="number" 
                                                    min="1"
                                                    :value="item.quantity"
                                                    @input="updateQuantity(item.id, $event.target.value)"
                                                    class="w-20 text-xs rounded border-gray-300"
                                                />
                                            </div>

                                            <div class="flex items-center gap-2 pt-2 border-t border-gray-200 dark:border-gray-700">
                                                <label class="text-xs text-gray-600 dark:text-gray-400 w-16">Subtotal:</label>
                                                <span class="text-xs font-semibold text-white">₱<span x-text="formatNumber(item.price * item.quantity)"></span></span>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <div x-show="selectedCount === 0" class="text-center text-gray-500 text-sm py-8">
                                No items selected yet
                            </div>

                            <!-- Submit Button -->
                            <div class="mt-6 flex gap-2">
                                <a href="{{ route('purchase-requests.index') }}" 
                                   class="flex-1 text-center px-4 py-2 bg-gray-500 hover:bg-gray-700 text-white font-bold rounded">
                                    Cancel
                                </a>
                                <button 
                                    type="submit"
                                    form="prForm"
                                    :disabled="!canSubmit"
                                    class="flex-1 px-4 py-2 bg-blue-500 hover:bg-blue-700 text-white font-bold rounded disabled:opacity-50 disabled:cursor-not-allowed">
                                    Submit Replacement
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Custom Price Modal -->
        <div x-show="showPriceModal" 
             x-cloak
             class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center"
             @click.self="closePriceModal">
            <div class="relative p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
                <div class="mt-3">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-4">Set Custom Price</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        Item: <span class="font-semibold" x-text="priceModalItem.name"></span>
                    </p>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Unit Price (₱)
                        </label>
                        <input 
                            type="number" 
                            x-model="priceModalItem.customPrice"
                            step="0.01" 
                            min="0.01"
                            placeholder="Enter price"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            @keydown.enter="saveCustomPrice"
                            @keydown.escape="closePriceModal"
                        />
                    </div>
                    <div class="flex gap-2 justify-end">
                        <button 
                            type="button"
                            @click="closePriceModal"
                            class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                            Cancel
                        </button>
                        <button 
                            type="button"
                            @click="saveCustomPrice"
                            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Save Price
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function prManager() {
            return {
                searchQuery: '',
                expandedCategories: [],
                selectedItems: [],
                budgetAvailable: {{ $departmentBudget->getAvailableBudget() }},
                showPriceModal: false,
                priceModalItem: { id: null, name: '', customPrice: null, data: null },
                prDetails: {
                    purpose: '{{ old('purpose', $originalPr->purpose) }}',
                    justification: '{{ old('justification', $originalPr->justification) }}'
                },

                init() {
                    // Load expanded state from localStorage
                    const stored = localStorage.getItem('prExpandedCategories');
                    if (stored) {
                        this.expandedCategories = JSON.parse(stored);
                    }

                    // Pre-populate with original PR items
                    @foreach($originalPr->items as $originalItem)
                        this.selectedItems.push({
                            id: Date.now() + {{ $loop->index }},
                            ppmpItemId: {{ $originalItem->ppmp_item_id ?? 'null' }},
                            name: '{{ addslashes($originalItem->item_name) }}',
                            code: '{{ $originalItem->item_code ?? '' }}',
                            unit: '{{ $originalItem->unit_of_measure }}',
                            price: {{ $originalItem->estimated_unit_cost }},
                            specs: '{{ addslashes($originalItem->detailed_specifications ?? '') }}',
                            quantity: {{ $originalItem->quantity_requested }},
                            isPriceEditable: {{ str_contains(strtoupper($originalItem->item_category ?? ''), 'SOFTWARE') || str_contains(strtoupper($originalItem->item_category ?? ''), 'PART') ? 'true' : 'false' }}
                        });
                    @endforeach
                },

                get selectedCount() {
                    return this.selectedItems.length;
                },

                get prTotal() {
                    return this.selectedItems.reduce((sum, item) => sum + (item.price * item.quantity), 0);
                },

                get budgetRemaining() {
                    return this.budgetAvailable - this.prTotal;
                },

                get canSubmit() {
                    return this.selectedCount > 0 && 
                           this.budgetRemaining >= 0 && 
                           this.prDetails.purpose.trim() !== '';
                },

                isSelected(itemId) {
                    return this.selectedItems.some(item => item.ppmpItemId === itemId);
                },

                toggleCategory(category) {
                    const index = this.expandedCategories.indexOf(category);
                    if (index > -1) {
                        this.expandedCategories.splice(index, 1);
                    } else {
                        this.expandedCategories.push(category);
                    }
                    localStorage.setItem('prExpandedCategories', JSON.stringify(this.expandedCategories));
                },

                addItem(itemId, itemData) {
                    if (this.isSelected(itemId)) {
                        return;
                    }

                    if (itemData.isPriceEditable) {
                        // Show modal for custom price
                        this.priceModalItem = {
                            id: itemId,
                            name: itemData.name,
                            customPrice: itemData.price,
                            data: itemData
                        };
                        this.showPriceModal = true;
                    } else {
                        // Add directly with fixed price
                        this.selectedItems.push({
                            id: Date.now(),
                            ppmpItemId: itemId,
                            name: itemData.name,
                            code: itemData.code,
                            unit: itemData.unit,
                            price: itemData.price,
                            specs: itemData.specs,
                            quantity: 1,
                            isPriceEditable: false
                        });
                    }
                },

                saveCustomPrice() {
                    const price = parseFloat(this.priceModalItem.customPrice);
                    if (!price || price <= 0) {
                        alert('Please enter a valid price greater than 0');
                        return;
                    }

                    const data = this.priceModalItem.data;
                    this.selectedItems.push({
                        id: Date.now(),
                        ppmpItemId: this.priceModalItem.id,
                        name: data.name,
                        code: data.code,
                        unit: data.unit,
                        price: price,
                        specs: data.specs,
                        quantity: 1,
                        isPriceEditable: true
                    });

                    this.closePriceModal();
                },

                closePriceModal() {
                    this.showPriceModal = false;
                    this.priceModalItem = { id: null, name: '', customPrice: null, data: null };
                },

                removeItem(itemId) {
                    const index = this.selectedItems.findIndex(item => item.id === itemId);
                    if (index > -1) {
                        this.selectedItems.splice(index, 1);
                    }
                },

                updateQuantity(itemId, value) {
                    const item = this.selectedItems.find(i => i.id === itemId);
                    if (item) {
                        item.quantity = parseInt(value) || 1;
                    }
                },

                updatePrice(itemId, value) {
                    const item = this.selectedItems.find(i => i.id === itemId);
                    if (item && item.isPriceEditable) {
                        const newPrice = parseFloat(value);
                        if (!isNaN(newPrice) && newPrice >= 0) {
                            item.price = newPrice;
                        }
                    }
                },

                categoryVisible(category, itemNames, itemCodes) {
                    if (!this.searchQuery) return true;
                    const query = this.searchQuery.toLowerCase();
                    
                    if (category.toLowerCase().includes(query)) return true;
                    
                    return itemNames.some(name => name.toLowerCase().includes(query)) ||
                           itemCodes.some(code => code.toLowerCase().includes(query));
                },

                itemVisible(itemName, itemCode, category) {
                    if (!this.searchQuery) return true;
                    const query = this.searchQuery.toLowerCase();
                    return itemName.toLowerCase().includes(query) ||
                           itemCode.toLowerCase().includes(query) ||
                           category.toLowerCase().includes(query);
                },

                prepareSubmit(event) {
                    // Validate before submit
                    if (!this.canSubmit) {
                        event.preventDefault();
                        if (this.selectedCount === 0) {
                            alert('Please select at least one item.');
                        } else if (this.budgetRemaining < 0) {
                            alert('Total cost exceeds available budget.');
                        } else if (this.prDetails.purpose.trim() === '') {
                            alert('Please enter the purpose of the purchase request.');
                        }
                        return;
                    }

                    // Add hidden inputs for form submission
                    const form = event.target;
                    
                    // Remove old dynamic inputs
                    form.querySelectorAll('.dynamic-input').forEach(el => el.remove());

                    // Add selected items data
                    this.selectedItems.forEach((item, index) => {
                        this.addHiddenInput(form, `items[${index}][ppmp_item_id]`, item.ppmpItemId);
                        this.addHiddenInput(form, `items[${index}][item_code]`, item.code);
                        this.addHiddenInput(form, `items[${index}][item_name]`, item.name);
                        this.addHiddenInput(form, `items[${index}][unit_of_measure]`, item.unit);
                        this.addHiddenInput(form, `items[${index}][detailed_specifications]`, item.specs);
                        this.addHiddenInput(form, `items[${index}][quantity_requested]`, item.quantity);
                        this.addHiddenInput(form, `items[${index}][estimated_unit_cost]`, item.price);
                    });
                },

                addHiddenInput(form, name, value) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = name;
                    input.value = value || '';
                    input.className = 'dynamic-input';
                    form.appendChild(input);
                },

                formatNumber(num) {
                    return parseFloat(num || 0).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                }
            }
        }
    </script>
    @endpush
</x-app-layout>
