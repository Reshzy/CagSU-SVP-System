<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-black leading-tight">
            {{ __('Create/Edit PPMP') }} - FY {{ $fiscalYear }}
        </h2>
    </x-slot>

    <div class="py-12" x-data="ppmpManager()">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if ($errors->any())
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <ul>
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
                                <div class="text-2xl font-bold text-white">₱<span x-text="formatNumber(budgetAvailable)">{{ number_format($budgetStatus['allocated'], 2) }}</span></div>
                            </div>
                            <div class="mt-2 flex justify-between items-center">
                                <span class="text-sm text-gray-600 dark:text-gray-400">PPMP Total: ₱<span x-text="formatNumber(ppmpTotal)">0.00</span></span>
                                <span class="text-sm" :class="budgetRemaining >= 0 ? 'text-green-600' : 'text-red-600'">
                                    Remaining: ₱<span x-text="formatNumber(budgetRemaining)">{{ number_format($budgetStatus['allocated'], 2) }}</span>
                                </span>
                            </div>
                            <div class="mt-2 w-full bg-gray-200 rounded-full h-2">
                                <div class="h-2 rounded-full transition-all duration-300" 
                                     :class="budgetRemaining >= 0 ? 'bg-blue-600' : 'bg-red-600'"
                                     :style="`width: ${Math.min(100, (ppmpTotal / budgetAvailable) * 100)}%`">
                                </div>
                            </div>
                        </div>
                    </div>

                    <form action="{{ route('ppmp.store') }}" method="POST" id="ppmpForm" @submit="prepareSubmit">
                        @csrf
                        
                        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="text-lg font-bold text-gray-800 dark:text-gray-200">Select Items from APP</h3>
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
                                @foreach ($categories as $categoryIndex => $category)
                                    @php
                                        $categoryItems = $appItems[$category];
                                    @endphp
                                    <div class="mb-4 border border-gray-200 dark:border-gray-700 rounded-lg"
                                         x-show="categoryVisible('{{ $category }}', {{ json_encode($categoryItems->pluck('item_name')->toArray()) }}, {{ json_encode($categoryItems->pluck('item_code')->toArray()) }})">
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
                                                    {{ $categoryItems->count() }} items
                                                </span>
                                            </div>
                                            <span class="text-sm text-gray-600 dark:text-gray-400" x-show="categorySelectedCount('{{ $category }}') > 0">
                                                <span x-text="categorySelectedCount('{{ $category }}')"></span> selected
                                            </span>
                                        </div>

                                        <!-- Category Items -->
                                        <div x-show="expandedCategories.includes('{{ $category }}')" 
                                             x-transition
                                             class="p-4 space-y-2">
                                            @foreach ($categoryItems as $itemIndex => $appItem)
                                                @php
                                                    $existingItem = $existingItems->get($appItem->id);
                                                    $uniqueKey = $categoryIndex . '_' . $itemIndex;
                                                @endphp
                                                <div class="border border-gray-200 dark:border-gray-700 rounded p-3"
                                                     x-show="itemVisible('{{ $appItem->item_name }}', '{{ $appItem->item_code }}', '{{ $category }}')"
                                                     x-data="{ 
                                                         itemId: {{ $appItem->id }},
                                                         needsCustomPrice: {{ $appItem->unit_price > 0 ? 'false' : 'true' }},
                                                         customPrice: {{ $existingItem && $existingItem->estimated_unit_cost != $appItem->unit_price ? $existingItem->estimated_unit_cost : 'null' }}
                                                     }">
                                                    <div class="grid grid-cols-12 gap-2 items-center">
                                                        <div class="col-span-4">
                                                            <label class="flex items-center">
                                                                <input
                                                                    type="checkbox"
                                                                    :checked="isSelected({{ $appItem->id }})"
                                                                    @change="toggleItem({{ $appItem->id }}, '{{ addslashes($appItem->item_name) }}', {{ $appItem->unit_price ?? 0 }}, needsCustomPrice, $event.target.checked)"
                                                                    class="item-checkbox rounded"
                                                                />
                                                                <span class="ml-2 text-sm">
                                                                    <strong class="text-white">{{ $appItem->item_name }}</strong><br/>
                                                                    <span class="text-xs text-gray-500">{{ $appItem->item_code }}</span><br/>
                                                                    @if($appItem->unit_price > 0)
                                                                        <span class="text-xs text-gray-500">₱{{ number_format($appItem->unit_price, 2) }} / {{ $appItem->unit_of_measure }}</span>
                                                                    @else
                                                                        <span class="text-xs text-orange-500 font-semibold">Price TBD (Custom)</span>
                                                                    @endif
                                                                </span>
                                                            </label>
                                                        </div>
                                                        <div class="col-span-2">
                                                            <label class="text-xs text-gray-600 dark:text-gray-400">Q1</label>
                                                            <input
                                                                type="number"
                                                                :value="getQuantity({{ $appItem->id }}, 'q1')"
                                                                @input="updateQuantity({{ $appItem->id }}, 'q1', $event.target.value)"
                                                                min="0"
                                                                class="qty-input w-full rounded text-sm"
                                                            />
                                                        </div>
                                                        <div class="col-span-2">
                                                            <label class="text-xs text-gray-600 dark:text-gray-400">Q2</label>
                                                            <input
                                                                type="number"
                                                                :value="getQuantity({{ $appItem->id }}, 'q2')"
                                                                @input="updateQuantity({{ $appItem->id }}, 'q2', $event.target.value)"
                                                                min="0"
                                                                class="qty-input w-full rounded text-sm"
                                                            />
                                                        </div>
                                                        <div class="col-span-2">
                                                            <label class="text-xs text-gray-600 dark:text-gray-400">Q3</label>
                                                            <input
                                                                type="number"
                                                                :value="getQuantity({{ $appItem->id }}, 'q3')"
                                                                @input="updateQuantity({{ $appItem->id }}, 'q3', $event.target.value)"
                                                                min="0"
                                                                class="qty-input w-full rounded text-sm"
                                                            />
                                                        </div>
                                                        <div class="col-span-2">
                                                            <label class="text-xs text-gray-600 dark:text-gray-400">Q4</label>
                                                            <input
                                                                type="number"
                                                                :value="getQuantity({{ $appItem->id }}, 'q4')"
                                                                @input="updateQuantity({{ $appItem->id }}, 'q4', $event.target.value)"
                                                                min="0"
                                                                class="qty-input w-full rounded text-sm"
                                                            />
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach

                                <div class="flex gap-2 mt-6">
                                    <button
                                        type="submit"
                                        class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                                    >
                                        Save PPMP
                                    </button>
                                    <a
                                        href="{{ route('ppmp.index') }}"
                                        class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"
                                    >
                                        Cancel
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Sticky Summary Sidebar -->
                <div class="w-80 sticky top-4 self-start">
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-4">
                            <h3 class="text-lg font-bold mb-4 text-gray-800 dark:text-gray-200">Selected Items</h3>
                            
                            <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
                                <div class="flex justify-between mb-2">
                                    <span>Total Items:</span>
                                    <span class="font-semibold" x-text="selectedCount">0</span>
                                </div>
                                <div class="flex justify-between mb-2">
                                    <span>Total Cost:</span>
                                    <span class="font-semibold text-white">₱<span x-text="formatNumber(ppmpTotal)">0.00</span></span>
                                </div>
                            </div>

                            <div class="max-h-96 overflow-y-auto space-y-2" x-show="selectedCount > 0">
                                <template x-for="item in selectedItems" :key="item.id">
                                    <div class="bg-gray-50 dark:bg-gray-900 p-2 rounded text-sm">
                                        <div class="flex justify-between items-start">
                                            <div class="flex-1">
                                                <div class="font-semibold text-gray-800 dark:text-gray-200" x-text="item.name"></div>
                                                <div class="text-xs text-gray-500">
                                                    Qty: <span x-text="item.totalQty"></span> | 
                                                    ₱<span x-text="formatNumber(item.price)"></span>
                                                    <span x-show="item.needsCustomPrice" class="text-orange-500">(Custom)</span>
                                                </div>
                                                <div class="text-xs font-semibold text-white">
                                                    Total: ₱<span x-text="formatNumber(item.totalCost)"></span>
                                                </div>
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
                                    </div>
                                </template>
                            </div>

                            <div x-show="selectedCount === 0" class="text-center text-gray-500 text-sm py-8">
                                No items selected yet
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Custom Price Modal -->
        <div x-show="showPriceModal" 
             x-cloak
             class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
             @click.self="showPriceModal = false">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
                <div class="mt-3">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-4">Set Custom Price</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        This item requires a custom price. Please enter the estimated unit cost:
                    </p>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Item: <span class="font-semibold" x-text="priceModalItem.name"></span>
                        </label>
                        <input 
                            type="number" 
                            x-model="priceModalItem.customPrice"
                            step="0.01" 
                            min="0.01"
                            placeholder="Enter price"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            @keydown.enter="saveCustomPrice"
                        />
                    </div>
                    <div class="flex gap-2 justify-end">
                        <button 
                            type="button"
                            @click="showPriceModal = false; removeItem(priceModalItem.id)"
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
        function ppmpManager() {
            return {
                searchQuery: '',
                expandedCategories: [],
                selectedItems: [],
                budgetAvailable: {{ $budgetStatus['allocated'] }},
                showPriceModal: false,
                priceModalItem: { id: null, name: '', customPrice: null },

                init() {
                    // Load existing items
                    @foreach($existingItems as $existingItem)
                        this.selectedItems.push({
                            id: {{ $existingItem->app_item_id }},
                            name: "{{ addslashes($existingItem->appItem->item_name) }}",
                            price: {{ $existingItem->estimated_unit_cost }},
                            needsCustomPrice: {{ $existingItem->appItem->unit_price > 0 ? 'false' : 'true' }},
                            q1: {{ $existingItem->q1_quantity }},
                            q2: {{ $existingItem->q2_quantity }},
                            q3: {{ $existingItem->q3_quantity }},
                            q4: {{ $existingItem->q4_quantity }},
                            totalQty: {{ $existingItem->total_quantity }},
                            totalCost: {{ $existingItem->estimated_total_cost }}
                        });
                    @endforeach

                    // Store expanded state in localStorage
                    const stored = localStorage.getItem('ppmpExpandedCategories');
                    if (stored) {
                        this.expandedCategories = JSON.parse(stored);
                    }
                },

                get selectedCount() {
                    return this.selectedItems.length;
                },

                get ppmpTotal() {
                    return this.selectedItems.reduce((sum, item) => sum + item.totalCost, 0);
                },

                get budgetRemaining() {
                    return this.budgetAvailable - this.ppmpTotal;
                },

                isSelected(itemId) {
                    return this.selectedItems.some(item => item.id === itemId);
                },

                toggleCategory(category) {
                    const index = this.expandedCategories.indexOf(category);
                    if (index > -1) {
                        this.expandedCategories.splice(index, 1);
                    } else {
                        this.expandedCategories.push(category);
                    }
                    localStorage.setItem('ppmpExpandedCategories', JSON.stringify(this.expandedCategories));
                },

                toggleItem(itemId, itemName, unitPrice, needsCustomPrice, isChecked) {
                    if (isChecked) {
                        // Adding item
                        if (needsCustomPrice) {
                            // Show modal for custom price
                            this.priceModalItem = {
                                id: itemId,
                                name: itemName,
                                customPrice: null
                            };
                            this.showPriceModal = true;
                        } else {
                            // Add with default price
                            this.selectedItems.push({
                                id: itemId,
                                name: itemName,
                                price: unitPrice,
                                needsCustomPrice: false,
                                q1: 0,
                                q2: 0,
                                q3: 0,
                                q4: 0,
                                totalQty: 0,
                                totalCost: 0
                            });
                        }
                    } else {
                        // Removing item
                        this.removeItem(itemId);
                    }
                },

                saveCustomPrice() {
                    const price = parseFloat(this.priceModalItem.customPrice);
                    if (!price || price <= 0) {
                        alert('Please enter a valid price greater than 0');
                        return;
                    }

                    this.selectedItems.push({
                        id: this.priceModalItem.id,
                        name: this.priceModalItem.name,
                        price: price,
                        needsCustomPrice: true,
                        q1: 0,
                        q2: 0,
                        q3: 0,
                        q4: 0,
                        totalQty: 0,
                        totalCost: 0
                    });

                    this.showPriceModal = false;
                    this.priceModalItem = { id: null, name: '', customPrice: null };
                },

                removeItem(itemId) {
                    const index = this.selectedItems.findIndex(item => item.id === itemId);
                    if (index > -1) {
                        this.selectedItems.splice(index, 1);
                    }
                },

                getQuantity(itemId, quarter) {
                    const item = this.selectedItems.find(item => item.id === itemId);
                    return item ? item[quarter] : 0;
                },

                updateQuantity(itemId, quarter, value) {
                    const item = this.selectedItems.find(item => item.id === itemId);
                    if (item) {
                        item[quarter] = parseInt(value) || 0;
                        item.totalQty = item.q1 + item.q2 + item.q3 + item.q4;
                        item.totalCost = item.totalQty * item.price;
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

                categorySelectedCount(category) {
                    // This is a simplified version - in production you'd track by category
                    return 0;
                },

                prepareSubmit(event) {
                    // Add hidden inputs for form submission
                    const form = event.target;
                    
                    // Remove old hidden inputs
                    form.querySelectorAll('.dynamic-input').forEach(el => el.remove());

                    // Add selected items data
                    this.selectedItems.forEach((item, index) => {
                        if (item.totalQty > 0) {
                            this.addHiddenInput(form, `items[${index}][app_item_id]`, item.id);
                            this.addHiddenInput(form, `items[${index}][q1_quantity]`, item.q1);
                            this.addHiddenInput(form, `items[${index}][q2_quantity]`, item.q2);
                            this.addHiddenInput(form, `items[${index}][q3_quantity]`, item.q3);
                            this.addHiddenInput(form, `items[${index}][q4_quantity]`, item.q4);
                            if (item.needsCustomPrice) {
                                this.addHiddenInput(form, `items[${index}][custom_unit_price]`, item.price);
                            }
                        }
                    });
                },

                addHiddenInput(form, name, value) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = name;
                    input.value = value;
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
