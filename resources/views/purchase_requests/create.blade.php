@section('title', 'New Purchase Request')

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-gray-800 leading-tight">{{ __('Create Purchase Request') }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-screen-2xl mx-auto sm:px-6 lg:px-8">
            @if ($errors->any())
            <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                <strong class="font-bold">Error!</strong>
                <ul class="mt-2 list-disc list-inside">
                    @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <form action="{{ route('purchase-requests.store') }}" method="POST" enctype="multipart/form-data" id="prForm">
                @csrf

                <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
                    <!-- Left Column: PPMP Catalog (40% - 2/5) -->
                    <div class="lg:col-span-2">
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg sticky top-4">
                            <div class="p-6">
                                <h3 class="text-lg font-semibold mb-4">PPMP Catalog</h3>

                                <!-- Search Box + Close All button -->
                                <div class="mb-4">
                                    <div class="flex items-center space-x-2">
                                        <input type="text" id="ppmpSearch"
                                            placeholder="Search items by name or code..."
                                            class="flex-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <button type="button" id="closeAllCategoriesBtn" title="Close all categories"
                                            class="px-3 py-2 bg-gray-200 text-gray-700 text-sm rounded hover:bg-gray-300">Close all</button>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1">Tip: Search will automatically show matching categories</p>
                                </div>

                                <!-- Categories Accordion (main first, then Part 2 separated) -->
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
                                    $catIndex = 0;
                                @endphp

                                <div class="space-y-2 max-h-[600px] overflow-y-auto" id="ppmpCatalog">
                                    @foreach($mainCategories as $category => $items)
                                    <div class="border border-gray-200 rounded-lg">
                                        <button type="button"
                                            class="w-full px-4 py-3 text-left font-medium text-gray-700 hover:bg-gray-50 flex justify-between items-center category-toggle"
                                            data-category="{{ $catIndex }}">
                                            <span class="text-sm">{{ $category }}</span>
                                            <svg class="w-5 h-5 transform transition-transform category-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                            </svg>
                                        </button>

                                        <div class="category-items hidden px-4 py-2 space-y-2 bg-gray-50" data-category="{{ $catIndex }}">
                                            @foreach($items as $item)
                                            @php
                                                $isPriceEditable = str_contains(strtoupper($category), 'SOFTWARE') || 
                                                                   str_contains(strtoupper($category), 'PART II') || 
                                                                   str_contains(strtoupper($category), 'OTHER ITEMS');
                                            @endphp
                                            <div class="border border-gray-300 rounded p-3 hover:border-indigo-500 bg-white ppmp-item"
                                                data-search="{{ strtolower($item->item_name . ' ' . $item->item_code . ' ' . $category) }}"
                                                data-category-name="{{ $category }}">
                                                <div class="flex justify-between items-start mb-2">
                                                    <div class="flex-1">
                                                        <div class="text-sm font-medium text-gray-900">{{ $item->item_name }}</div>
                                                        <div class="text-xs text-gray-500">{{ $item->item_code }}</div>
                                                        @if($isPriceEditable)
                                                            <span class="inline-block mt-1 px-2 py-0.5 text-xs bg-yellow-100 text-yellow-800 rounded">Custom Price</span>
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="flex justify-between items-center mt-2">
                                                    <div class="flex-1">
                                                        <span class="text-xs text-gray-600">{{ $item->unit_of_measure }}</span>
                                                        @if($isPriceEditable)
                                                            <div class="mt-1">
                                                                <span class="text-xs text-gray-600">Default: ₱{{ number_format($item->unit_price, 2) }}</span>
                                                            </div>
                                                        @else
                                                            <span class="text-sm font-semibold text-gray-900 ml-2">₱{{ number_format($item->unit_price, 2) }}</span>
                                                        @endif
                                                    </div>
                                                    <button type="button"
                                                        class="add-to-pr px-3 py-1 bg-indigo-600 text-white text-xs rounded hover:bg-indigo-700"
                                                        data-id="{{ $item->id }}"
                                                        data-name="{{ $item->item_name }}"
                                                        data-code="{{ $item->item_code }}"
                                                        data-unit="{{ $item->unit_of_measure }}"
                                                        data-price="{{ $item->unit_price }}"
                                                        data-specs="{{ $item->specifications }}"
                                                        data-price-editable="{{ $isPriceEditable ? 'true' : 'false' }}">
                                                        Add to PR
                                                    </button>
                                                </div>
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    @php $catIndex++; @endphp
                                    @endforeach

                                    @if(count($part2Categories) > 0)
                                    <div class="border-t pt-4 mt-4">
                                        <h4 class="text-sm font-semibold text-gray-700 mb-2">Part 2 / Other Items</h4>
                                        @foreach($part2Categories as $category => $items)
                                        <div class="border border-gray-200 rounded-lg mt-2">
                                            <button type="button"
                                                class="w-full px-4 py-3 text-left font-medium text-gray-700 hover:bg-gray-50 flex justify-between items-center category-toggle"
                                                data-category="{{ $catIndex }}">
                                                <span class="text-sm">{{ $category }}</span>
                                                <svg class="w-5 h-5 transform transition-transform category-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                </svg>
                                            </button>

                                            <div class="category-items hidden px-4 py-2 space-y-2 bg-gray-50" data-category="{{ $catIndex }}">
                                                @foreach($items as $item)
                                                @php
                                                    $isPriceEditable = str_contains(strtoupper($category), 'SOFTWARE') || 
                                                                       str_contains(strtoupper($category), 'PART II') || 
                                                                       str_contains(strtoupper($category), 'OTHER ITEMS');
                                                @endphp
                                                <div class="border border-gray-300 rounded p-3 hover:border-indigo-500 bg-white ppmp-item"
                                                    data-search="{{ strtolower($item->item_name . ' ' . $item->item_code . ' ' . $category) }}"
                                                    data-category-name="{{ $category }}">
                                                    <div class="flex justify-between items-start mb-2">
                                                        <div class="flex-1">
                                                            <div class="text-sm font-medium text-gray-900">{{ $item->item_name }}</div>
                                                            <div class="text-xs text-gray-500">{{ $item->item_code }}</div>
                                                            @if($isPriceEditable)
                                                                <span class="inline-block mt-1 px-2 py-0.5 text-xs bg-yellow-100 text-yellow-800 rounded">Custom Price</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                    <div class="flex justify-between items-center mt-2">
                                                        <div class="flex-1">
                                                            <span class="text-xs text-gray-600">{{ $item->unit_of_measure }}</span>
                                                            @if($isPriceEditable)
                                                                <div class="mt-1">
                                                                    <span class="text-xs text-gray-600">Default: ₱{{ number_format($item->unit_price, 2) }}</span>
                                                                </div>
                                                            @else
                                                                <span class="text-sm font-semibold text-gray-900 ml-2">₱{{ number_format($item->unit_price, 2) }}</span>
                                                            @endif
                                                        </div>
                                                        <button type="button"
                                                            class="add-to-pr px-3 py-1 bg-indigo-600 text-white text-xs rounded hover:bg-indigo-700"
                                                            data-id="{{ $item->id }}"
                                                            data-name="{{ $item->item_name }}"
                                                            data-code="{{ $item->item_code }}"
                                                            data-unit="{{ $item->unit_of_measure }}"
                                                            data-price="{{ $item->unit_price }}"
                                                            data-specs="{{ $item->specifications }}"
                                                            data-price-editable="{{ $isPriceEditable ? 'true' : 'false' }}">
                                                            Add to PR
                                                        </button>
                                                    </div>
                                                </div>
                                                @endforeach
                                            </div>
                                        </div>
                                        @php $catIndex++; @endphp
                                        @endforeach
                                    </div>
                                    @endif
                                </div>

                                <!-- Info Note -->
                                <div class="mt-6 border-t pt-4">
                                    <div class="p-3 bg-blue-50 border border-blue-200 rounded-md">
                                        <p class="text-xs text-blue-800">
                                            <strong>Note:</strong> All items shown are from the official PS-DBM PPMP catalog. 
                                            Software and Part II items allow custom pricing as they may vary by supplier.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: PR Form (60% - 3/5) -->
                    <div class="lg:col-span-3 space-y-6">
                        <!-- Budget Summary Card -->
                        @if($departmentBudget)
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-semibold mb-4">Budget Summary (FY {{ $fiscalYear }})</h3>

                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                    <div class="bg-blue-50 p-3 rounded">
                                        <div class="text-xs text-gray-600">Allocated</div>
                                        <div class="text-lg font-bold text-blue-600">₱{{ number_format($departmentBudget->allocated_budget, 2) }}</div>
                                    </div>

                                    <div class="bg-red-50 p-3 rounded">
                                        <div class="text-xs text-gray-600">Utilized</div>
                                        <div class="text-lg font-bold text-red-600">₱{{ number_format($departmentBudget->utilized_budget, 2) }}</div>
                                    </div>

                                    <div class="bg-yellow-50 p-3 rounded">
                                        <div class="text-xs text-gray-600">Reserved</div>
                                        <div class="text-lg font-bold text-yellow-600">₱{{ number_format($departmentBudget->reserved_budget, 2) }}</div>
                                    </div>

                                    <div class="bg-green-50 p-3 rounded">
                                        <div class="text-xs text-gray-600">Available</div>
                                        <div class="text-lg font-bold text-green-600" id="availableBudget">
                                            ₱{{ number_format($departmentBudget->getAvailableBudget(), 2) }}
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4 p-3 bg-purple-50 rounded">
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-700 font-medium">Current PR Total:</span>
                                        <span class="text-xl font-bold text-purple-600" id="currentPrTotal">₱0.00</span>
                                    </div>
                                    <div class="mt-2 flex justify-between items-center text-sm">
                                        <span class="text-gray-600">Remaining After PR:</span>
                                        <span class="font-semibold" id="remainingAfterPr">₱{{ number_format($departmentBudget->getAvailableBudget(), 2) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @else
                        <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded">
                            Warning: No budget information available. Please contact your budget officer.
                        </div>
                        @endif

                        <!-- PR Basic Information -->
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6 space-y-4">
                                <h3 class="text-lg font-semibold mb-4">Purchase Request Details</h3>

                                <div>
                                    <x-input-label for="purpose" value="Purpose" />
                                    <x-text-input id="purpose" name="purpose" type="text" class="mt-1 block w-full" :value="old('purpose')" required />
                                    <x-input-error :messages="$errors->get('purpose')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="justification" value="Justification" />
                                    <textarea id="justification" name="justification" class="mt-1 block w-full border-gray-300 rounded-md" rows="3">{{ old('justification') }}</textarea>
                                    <x-input-error :messages="$errors->get('justification')" class="mt-2" />
                                </div>
                            </div>
                        </div>

                        <!-- Selected Items -->
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-semibold mb-4">Selected Items</h3>

                                <div id="selectedItems" class="space-y-3">
                                    <div class="text-center text-gray-500 py-8" id="emptyState">
                                        No items selected yet. Add items from the PPMP catalog on the left.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Attachments -->
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <x-input-label for="attachments" value="Attachments (Optional)" />
                                <input id="attachments" name="attachments[]" type="file" multiple class="mt-1 block w-full" />
                                <p class="mt-1 text-sm text-gray-600">You can attach multiple files (max 10MB each)</p>
                                <x-input-error :messages="$errors->get('attachments.*')" class="mt-2" />
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex justify-end space-x-3">
                            <a href="{{ route('purchase-requests.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200">Cancel</a>
                            <button type="submit" id="submitBtn"
                                class="px-6 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed"
                                disabled>
                                Submit PR
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let selectedItems = [];
            let itemCounter = 0;
            const availableBudget = {{ $departmentBudget ? $departmentBudget->getAvailableBudget() : 0 }};

            // Category toggle via event delegation on the catalog container
            const ppmpCatalog = document.getElementById('ppmpCatalog');
            if (ppmpCatalog) {
                ppmpCatalog.addEventListener('click', function(e) {
                    // Find the closest toggle button clicked (or parent of an inner element)
                    const btn = e.target.closest('.category-toggle');
                    if (!btn) return;
                    e.preventDefault();

                    const parent = btn.parentElement;
                    let itemsDiv = null;

                    // Try adjacent sibling first
                    if (parent && parent.nextElementSibling && parent.nextElementSibling.classList.contains('category-items')) {
                        itemsDiv = parent.nextElementSibling;
                    }

                    // Fallback: query by data-category
                    if (!itemsDiv) {
                        const categoryIndex = btn.dataset.category;
                        itemsDiv = document.querySelector(`.category-items[data-category="${categoryIndex}"]`);
                    }

                    const svg = btn.querySelector('.category-arrow');

                    if (!itemsDiv) return;
                    itemsDiv.classList.toggle('hidden');
                    if (svg) svg.classList.toggle('rotate-180');
                });
            } else {
                // PPMP Catalog element not found; nothing to bind
            }

            // Search functionality with smart category opening/closing
            document.getElementById('ppmpSearch').addEventListener('input', function(e) {
                const searchTerm = e.target.value.toLowerCase().trim();
                
                if (!searchTerm) {
                    // Reset: show all items and close all categories
                    document.querySelectorAll('.ppmp-item').forEach(item => {
                        item.style.display = 'block';
                    });
                    document.querySelectorAll('.category-items').forEach(categoryDiv => {
                        categoryDiv.classList.add('hidden');
                    });
                    document.querySelectorAll('.category-arrow').forEach(arrow => {
                        arrow.classList.remove('rotate-180');
                    });
                    return;
                }
                
                // Track which categories have matches
                const categoriesWithMatches = new Set();
                
                // Search through items
                document.querySelectorAll('.ppmp-item').forEach(item => {
                    const searchData = item.dataset.search;
                    const categoryName = item.dataset.categoryName;
                    
                    if (searchData.includes(searchTerm)) {
                        item.style.display = 'block';
                        categoriesWithMatches.add(categoryName);
                    } else {
                        item.style.display = 'none';
                    }
                });
                
                // Open/close categories based on matches
                document.querySelectorAll('.category-toggle').forEach(btn => {
                    const categoryIndex = btn.dataset.category;
                    const categoryText = btn.querySelector('span').textContent.trim();
                    const categoryDiv = document.querySelector(`.category-items[data-category="${categoryIndex}"]`);
                    const arrow = btn.querySelector('.category-arrow');
                    
                    if (categoriesWithMatches.has(categoryText)) {
                        // Open category with matches
                        categoryDiv.classList.remove('hidden');
                        if (arrow) arrow.classList.add('rotate-180');
                    } else {
                        // Close category without matches
                        categoryDiv.classList.add('hidden');
                        if (arrow) arrow.classList.remove('rotate-180');
                    }
                });
            });

            // Close all categories button
            const closeAllBtn = document.getElementById('closeAllCategoriesBtn');
            if (closeAllBtn) {
                closeAllBtn.addEventListener('click', function() {
                    document.querySelectorAll('.category-items').forEach(div => div.classList.add('hidden'));
                    document.querySelectorAll('.category-arrow').forEach(arrow => arrow.classList.remove('rotate-180'));
                });
            }

            // Add to PR from PPMP
            // Price modal helpers
            const priceModal = document.createElement('div');
            // We'll insert an inline modal markup in DOM (created below in the blade). Use handlers to control it.

            let pendingPriceData = null;

            let __previousActiveElement = null;

            function openPriceModal(data) {
                pendingPriceData = data; // store button dataset values
                const overlay = document.getElementById('priceModal');
                const nameEl = document.getElementById('priceModalItemName');
                const input = document.getElementById('priceModalInput');
                const err = document.getElementById('priceModalError');

                if (!overlay || !input) return;

                // remember previous focused element to restore on close
                __previousActiveElement = document.activeElement;

                nameEl.textContent = data.name + ' — ' + data.unit + ' (default ₱' + parseFloat(data.price).toFixed(2) + ')';
                input.value = parseFloat(data.price).toFixed(2);
                err.classList.add('hidden');

                // show overlay (remove hidden)
                overlay.classList.remove('hidden');

                // focus input
                setTimeout(() => input.focus(), 50);
            }

            function closePriceModal() {
                const overlay = document.getElementById('priceModal');
                if (!overlay) return;

                // hide overlay
                overlay.classList.add('hidden');

                // restore focus
                if (__previousActiveElement && typeof __previousActiveElement.focus === 'function') {
                    try { __previousActiveElement.focus(); } catch (e) {}
                }

                pendingPriceData = null;
            }

            // Modal button handlers
            document.getElementById('priceModalSave')?.addEventListener('click', function() {
                const input = document.getElementById('priceModalInput');
                const err = document.getElementById('priceModalError');
                const val = parseFloat(input.value);
                if (isNaN(val) || val < 0) {
                    err.classList.remove('hidden');
                    err.textContent = 'Please enter a valid non-negative number';
                    return;
                }

                if (!pendingPriceData) {
                    closePriceModal();
                    return;
                }

                const item = {
                    id: itemCounter++,
                    ppmp_item_id: pendingPriceData.id,
                    name: pendingPriceData.name,
                    code: pendingPriceData.code,
                    unit: pendingPriceData.unit,
                    price: val,
                    defaultPrice: parseFloat(pendingPriceData.price),
                    specs: pendingPriceData.specs,
                    quantity: 1,
                    isPriceEditable: true
                };

                selectedItems.push(item);
                renderSelectedItems();
                updateTotals();
                closePriceModal();
            });

            // Attach cancel handlers to both cancel controls
            document.querySelectorAll('.priceModalCancel').forEach(btn => btn.addEventListener('click', closePriceModal));

            // Close modal on overlay click (only when clicking the backdrop, not the panel)
            document.getElementById('priceModal')?.addEventListener('click', function(e) {
                if (e.target === this) closePriceModal();
            });

            // Close modal on Escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    const overlay = document.getElementById('priceModal');
                    if (overlay && !overlay.classList.contains('hidden')) {
                        closePriceModal();
                    }
                }
            });

            document.querySelectorAll('.add-to-pr').forEach(button => {
                button.addEventListener('click', function() {
                    const isPriceEditable = this.dataset.priceEditable === 'true';
                    const defaultPrice = parseFloat(this.dataset.price);

                    if (isPriceEditable) {
                        // open modal and wait for user input
                        openPriceModal({
                            id: this.dataset.id,
                            name: this.dataset.name,
                            code: this.dataset.code,
                            unit: this.dataset.unit,
                            price: this.dataset.price,
                            specs: this.dataset.specs
                        });
                        return;
                    }

                    // Non-editable price: add directly
                    const item = {
                        id: itemCounter++,
                        ppmp_item_id: this.dataset.id,
                        name: this.dataset.name,
                        code: this.dataset.code,
                        unit: this.dataset.unit,
                        price: defaultPrice,
                        defaultPrice: defaultPrice,
                        specs: this.dataset.specs,
                        quantity: 1,
                        isPriceEditable: false
                    };

                    selectedItems.push(item);
                    renderSelectedItems();
                    updateTotals();
                });
            });

            function renderSelectedItems() {
                const container = document.getElementById('selectedItems');

                if (selectedItems.length === 0) {
                    // Recreate the empty state placeholder when there are no items
                    container.innerHTML = `
                        <div class="text-center text-gray-500 py-8" id="emptyState">
                            No items selected yet. Add items from the PPMP catalog on the left.
                        </div>
                    `;
                    return;
                }

                // Build list of selected items
                container.innerHTML = selectedItems.map(item => {
                    const isPriceCustomized = item.isPriceEditable && item.price !== item.defaultPrice;
                    const priceDisplay = item.isPriceEditable 
                        ? `<div class="text-sm text-gray-600 mt-1">
                             <span class="text-xs bg-yellow-100 text-yellow-800 px-2 py-0.5 rounded">Custom Price</span>
                             ${isPriceCustomized ? `<span class="text-xs text-gray-500 ml-2">(Default: ₱${item.defaultPrice.toFixed(2)})</span>` : ''}
                           </div>`
                        : '';
                    
                    return `
                    <div class="border border-gray-300 rounded-lg p-4 ${item.isPriceEditable ? 'border-yellow-300 bg-yellow-50' : ''}" data-item-id="${item.id}">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <div class="font-medium text-gray-900">${item.name}</div>
                                <div class="text-sm text-gray-500">${item.code}</div>
                                ${priceDisplay}
                            </div>
                            <button type="button" class="text-red-600 hover:text-red-800" onclick="removeItem(${item.id})">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                        <div class="mt-3 space-y-2">
                            ${item.isPriceEditable ? `
                            <div class="flex items-center space-x-2">
                                <label class="text-sm text-gray-700 w-24">Unit Price:</label>
                                <div class="flex items-center space-x-1">
                                    <span class="text-sm">₱</span>
                                    <input type="number" step="0.01" min="0" value="${item.price.toFixed(2)}"
                                        class="w-32 border-gray-300 rounded-md text-sm price-input"
                                        data-item-id="${item.id}"
                                        onchange="updatePrice(${item.id}, this.value)">
                                    <span class="text-xs text-gray-500">per ${item.unit}</span>
                                </div>
                            </div>
                            ` : `
                            <div class="flex items-center space-x-2">
                                <label class="text-sm text-gray-700 w-24">Unit Price:</label>
                                <span class="text-sm text-gray-900">₱${item.price.toFixed(2)} per ${item.unit}</span>
                            </div>
                            `}
                            <div class="flex items-center space-x-2">
                                <label class="text-sm text-gray-700 w-24">Quantity:</label>
                                <input type="number" min="1" value="${item.quantity}"
                                    class="w-24 border-gray-300 rounded-md quantity-input text-sm"
                                    data-item-id="${item.id}"
                                    onchange="updateQuantity(${item.id}, this.value)">
                            </div>
                            <div class="flex items-center space-x-2 pt-2 border-t">
                                <label class="text-sm text-gray-700 w-24 font-medium">Subtotal:</label>
                                <span class="text-base font-semibold text-gray-900">₱${(item.price * item.quantity).toFixed(2)}</span>
                            </div>
                        </div>
                        <input type="hidden" name="items[${item.id}][ppmp_item_id]" value="${item.ppmp_item_id || ''}">
                        <input type="hidden" name="items[${item.id}][item_name]" value="${escapeHtml(item.name)}">
                        <input type="hidden" name="items[${item.id}][unit_of_measure]" value="${escapeHtml(item.unit)}">
                        <input type="hidden" name="items[${item.id}][detailed_specifications]" value="${escapeHtml(item.specs)}">
                        <input type="hidden" name="items[${item.id}][quantity_requested]" value="${item.quantity}">
                        <input type="hidden" name="items[${item.id}][estimated_unit_cost]" value="${item.price}">
                    </div>
                    `;
                }).join('');
            }

            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            function removeItem(itemId) {
                selectedItems = selectedItems.filter(item => item.id !== itemId);
                renderSelectedItems();
                updateTotals();
            }

            function updateQuantity(itemId, quantity) {
                const item = selectedItems.find(i => i.id === itemId);
                if (item) {
                    item.quantity = parseInt(quantity) || 1;
                    renderSelectedItems();
                    updateTotals();
                }
            }

            function updatePrice(itemId, price) {
                const item = selectedItems.find(i => i.id === itemId);
                if (item && item.isPriceEditable) {
                    const newPrice = parseFloat(price);
                    if (!isNaN(newPrice) && newPrice >= 0) {
                        item.price = newPrice;
                        renderSelectedItems();
                        updateTotals();
                    }
                }
            }

            function updateTotals() {
                const total = selectedItems.reduce((sum, item) => sum + (item.price * item.quantity), 0);
                document.getElementById('currentPrTotal').textContent = '₱' + total.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');

                const remaining = availableBudget - total;
                const remainingEl = document.getElementById('remainingAfterPr');
                remainingEl.textContent = '₱' + remaining.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');

                if (remaining < 0) {
                    remainingEl.classList.add('text-red-600');
                    remainingEl.classList.remove('text-green-600');
                } else {
                    remainingEl.classList.add('text-green-600');
                    remainingEl.classList.remove('text-red-600');
                }

                // Enable/disable submit button
                const submitBtn = document.getElementById('submitBtn');
                if (selectedItems.length > 0 && remaining >= 0) {
                    submitBtn.disabled = false;
                } else {
                    submitBtn.disabled = true;
                }
            }

            // Make functions globally accessible for inline event handlers
            window.removeItem = removeItem;
            window.updateQuantity = updateQuantity;
            window.updatePrice = updatePrice;
        });
    </script>
        @include('components.price-modal')
        <script>
            // Focus trap for price modal
            (function() {
                function getFocusable(el) {
                    return Array.from(el.querySelectorAll('a[href], button:not([disabled]), textarea, input, select, [tabindex]:not([tabindex="-1"])')).filter(e => e.offsetParent !== null);
                }

                const overlay = document.getElementById('priceModal');
                const panel = document.getElementById('priceModalPanel');

                function trapFocus(e) {
                    if (!overlay || overlay.classList.contains('hidden')) return;
                    if (e.key !== 'Tab') return;
                    const focusables = getFocusable(panel);
                    if (focusables.length === 0) return;
                    const first = focusables[0];
                    const last = focusables[focusables.length - 1];

                    if (e.shiftKey) {
                        if (document.activeElement === first) {
                            e.preventDefault();
                            last.focus();
                        }
                    } else {
                        if (document.activeElement === last) {
                            e.preventDefault();
                            first.focus();
                        }
                    }
                }

                document.addEventListener('keydown', trapFocus);
            })();
        </script>
    @endpush

</x-app-layout>