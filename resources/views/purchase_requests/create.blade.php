<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-black leading-tight">
            {{ __('Create Purchase Request') }} - FY {{ $fiscalYear }}
        </h2>
    </x-slot>

    <div class="py-12" x-data="prManager()">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
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
                    <!-- Current Quarter Banner -->
                    <div class="bg-blue-50 dark:bg-blue-900 border-l-4 border-blue-500 p-4 mb-6 rounded-r-lg">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            <div class="ml-3 flex-1">
                                <h3 class="text-lg font-medium text-blue-800 dark:text-blue-200">
                                    Creating PR for Q{{ $currentQuarter }} - {{ $quarterLabel }}
                                </h3>
                                <p class="text-sm text-blue-700 dark:text-blue-300 mt-1">
                                    Only items allocated to the current quarter can be selected
                                </p>
                            </div>
                        </div>
                    </div>

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

                    <form action="{{ route('purchase-requests.store') }}" method="POST" enctype="multipart/form-data" id="prForm" @submit="prepareSubmit">
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
                                                <svg class="w-5 h-5 transition-transform duration-200 text-white" 
                                                     :class="{'rotate-90': expandedCategories.includes('{{ $category }}')}"
                                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                                </svg>
                                                <h4 class="text-md font-semibold text-gray-800 dark:text-gray-200">{{ $category }}</h4>
                                                <span class="text-center bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-blue-900 dark:text-blue-300">
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
                                                    
                                                    // Get quarter status for this item
                                                    $itemData = $categorizedItems[$category]->firstWhere('item.id', $item->id);
                                                    $quarterStatus = $itemData['quarterStatus'] ?? 'unavailable';
                                                    $remainingQty = $itemData['remainingQty'] ?? 0;
                                                    $currentQuarterQty = $itemData['currentQuarterQty'] ?? 0;
                                                    $isAvailable = $quarterStatus === 'current' && $remainingQty > 0;
                                                @endphp
                                                <div class="border border-gray-200 dark:border-gray-700 rounded p-3 transition-colors {{ $isAvailable ? 'hover:border-indigo-500' : 'bg-gray-50 dark:bg-gray-900 opacity-60' }}"
                                                     x-show="itemVisible('{{ $item->appItem->item_name }}', '{{ $item->appItem->item_code }}', '{{ $category }}')">
                                                    <div class="flex justify-between items-start">
                                                        <div class="flex-1">
                                                            <div class="flex items-start gap-2">
                                                                <div class="text-sm font-medium {{ $isAvailable ? 'text-gray-900 dark:text-gray-100' : 'text-gray-500 dark:text-gray-600' }}">
                                                                    {{ $item->appItem->item_name }}
                                                                </div>
                                                            </div>
                                                            
                                                            <!-- Quarter Status Badges -->
                                                            <div class="mt-1 flex flex-wrap gap-1">
                                                                @if($quarterStatus === 'current' && $remainingQty > 0)
                                                                    <span class="px-2 py-0.5 bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 rounded text-xs font-medium">
                                                                        Available - Q{{ $currentQuarter }}
                                                                    </span>
                                                                    <span class="px-2 py-0.5 bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 rounded text-xs">
                                                                        {{ $remainingQty }} remaining
                                                                    </span>
                                                                @elseif($quarterStatus === 'past')
                                                                    <span class="px-2 py-0.5 bg-gray-300 text-gray-700 dark:bg-gray-700 dark:text-gray-300 rounded text-xs">
                                                                        Past Quarter - Not Available
                                                                    </span>
                                                                @elseif($quarterStatus === 'future')
                                                                    <span class="px-2 py-0.5 bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300 rounded text-xs">
                                                                        Available in Q{{ $item->getNextAvailableQuarter() }} - {{ $item->getQuarterMonths() }}
                                                                    </span>
                                                                @elseif($quarterStatus === 'current' && $remainingQty <= 0)
                                                                    <span class="px-2 py-0.5 bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 rounded text-xs">
                                                                        Fully Utilized - Q{{ $currentQuarter }}
                                                                    </span>
                                                                @else
                                                                    <span class="px-2 py-0.5 bg-gray-300 text-gray-700 dark:bg-gray-700 dark:text-gray-300 rounded text-xs">
                                                                        Not Allocated
                                                                    </span>
                                                                @endif
                                                            </div>
                                                            
                                                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                                {{ $item->appItem->item_code }}
                                                            </div>
                                                            
                                                            @if($isAvailable)
                                                                <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                                                    {{ $item->appItem->unit_of_measure }}
                                                                    @if($isPriceEditable)
                                                                        <span class="ml-2 px-2 py-0.5 bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200 rounded text-xs">Custom Price</span>
                                                                    @else
                                                                        • <span class="font-semibold">₱{{ number_format($item->estimated_unit_cost, 2) }}</span>
                                                                    @endif
                                                                </div>
                                                            @else
                                                                <div class="text-xs text-gray-500 dark:text-gray-600 mt-1 italic">
                                                                    @if($quarterStatus === 'past')
                                                                        This item was allocated to a past quarter
                                                                    @elseif($quarterStatus === 'future')
                                                                        Wait until {{ $item->getQuarterMonths($item->getNextAvailableQuarter()) }} to request this item
                                                                    @elseif($quarterStatus === 'current' && $remainingQty <= 0)
                                                                        All {{ $currentQuarterQty }} units have been requested
                                                                    @else
                                                                        No quantity allocated for any quarter
                                                                    @endif
                                                                </div>
                                                            @endif
                                                        </div>
                                                        <button type="button"
                                                            @click="addItem({{ $item->id }}, {
                                                                id: {{ $item->id }},
                                                                name: '{{ addslashes($item->appItem->item_name) }}',
                                                                code: '{{ $item->appItem->item_code }}',
                                                                unit: '{{ $item->appItem->unit_of_measure }}',
                                                                price: {{ $item->estimated_unit_cost }},
                                                                specs: '{{ addslashes($item->appItem->specifications ?? '') }}',
                                                                isPriceEditable: {{ $isPriceEditable ? 'true' : 'false' }},
                                                                maxQuantity: {{ $remainingQty }}
                                                            })"
                                                            :disabled="isSelected({{ $item->id }}) || !{{ $isAvailable ? 'true' : 'false' }}"
                                                            class="px-3 py-1 text-xs rounded transition-colors {{ $isAvailable ? 'bg-indigo-600 text-white hover:bg-indigo-700' : 'bg-gray-300 text-gray-500 cursor-not-allowed' }} disabled:opacity-50 disabled:cursor-not-allowed">
                                                            @if($isAvailable)
                                                                <span x-show="!isSelected({{ $item->id }})">Add to PR</span>
                                                                <span x-show="isSelected({{ $item->id }})">Added</span>
                                                            @else
                                                                <span>Not Available</span>
                                                            @endif
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
                                                            
                                                            // Get quarter status for this item
                                                            $itemData = $categorizedItems[$category]->firstWhere('item.id', $item->id);
                                                            $quarterStatus = $itemData['quarterStatus'] ?? 'unavailable';
                                                            $remainingQty = $itemData['remainingQty'] ?? 0;
                                                            $currentQuarterQty = $itemData['currentQuarterQty'] ?? 0;
                                                            $isAvailable = $quarterStatus === 'current' && $remainingQty > 0;
                                                        @endphp
                                                        <div class="border border-gray-200 dark:border-gray-700 rounded p-3 transition-colors {{ $isAvailable ? 'hover:border-indigo-500' : 'bg-gray-50 dark:bg-gray-900 opacity-60' }}"
                                                             x-show="itemVisible('{{ $item->appItem->item_name }}', '{{ $item->appItem->item_code }}', '{{ $category }}')">
                                                            <div class="flex justify-between items-start">
                                                                <div class="flex-1">
                                                                    <div class="text-sm font-medium {{ $isAvailable ? 'text-gray-900 dark:text-gray-100' : 'text-gray-500 dark:text-gray-600' }}">
                                                                        {{ $item->appItem->item_name }}
                                                                    </div>
                                                                    
                                                                    <!-- Quarter Status Badges -->
                                                                    <div class="mt-1 flex flex-wrap gap-1">
                                                                        @if($quarterStatus === 'current' && $remainingQty > 0)
                                                                            <span class="px-2 py-0.5 bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 rounded text-xs font-medium">
                                                                                Available - Q{{ $currentQuarter }}
                                                                            </span>
                                                                            <span class="px-2 py-0.5 bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 rounded text-xs">
                                                                                {{ $remainingQty }} remaining
                                                                            </span>
                                                                        @elseif($quarterStatus === 'past')
                                                                            <span class="px-2 py-0.5 bg-gray-300 text-gray-700 dark:bg-gray-700 dark:text-gray-300 rounded text-xs">
                                                                                Past Quarter - Not Available
                                                                            </span>
                                                                        @elseif($quarterStatus === 'future')
                                                                            <span class="px-2 py-0.5 bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300 rounded text-xs">
                                                                                Available in Q{{ $item->getNextAvailableQuarter() }} - {{ $item->getQuarterMonths() }}
                                                                            </span>
                                                                        @elseif($quarterStatus === 'current' && $remainingQty <= 0)
                                                                            <span class="px-2 py-0.5 bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 rounded text-xs">
                                                                                Fully Utilized - Q{{ $currentQuarter }}
                                                                            </span>
                                                                        @else
                                                                            <span class="px-2 py-0.5 bg-gray-300 text-gray-700 dark:bg-gray-700 dark:text-gray-300 rounded text-xs">
                                                                                Not Allocated
                                                                            </span>
                                                                        @endif
                                                                    </div>
                                                                    
                                                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                                        {{ $item->appItem->item_code }}
                                                                    </div>
                                                                    
                                                                    @if($isAvailable)
                                                                        <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                                                            {{ $item->appItem->unit_of_measure }}
                                                                            <span class="ml-2 px-2 py-0.5 bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200 rounded text-xs">Custom Price</span>
                                                                        </div>
                                                                    @else
                                                                        <div class="text-xs text-gray-500 dark:text-gray-600 mt-1 italic">
                                                                            @if($quarterStatus === 'past')
                                                                                This item was allocated to a past quarter
                                                                            @elseif($quarterStatus === 'future')
                                                                                Wait until {{ $item->getQuarterMonths($item->getNextAvailableQuarter()) }} to request this item
                                                                            @elseif($quarterStatus === 'current' && $remainingQty <= 0)
                                                                                All {{ $currentQuarterQty }} units have been requested
                                                                            @else
                                                                                No quantity allocated for any quarter
                                                                            @endif
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                                <button type="button"
                                                                    @click="addItem({{ $item->id }}, {
                                                                        id: {{ $item->id }},
                                                                        name: '{{ addslashes($item->appItem->item_name) }}',
                                                                        code: '{{ $item->appItem->item_code }}',
                                                                        unit: '{{ $item->appItem->unit_of_measure }}',
                                                                        price: {{ $item->estimated_unit_cost }},
                                                                        specs: '{{ addslashes($item->appItem->specifications ?? '') }}',
                                                                        isPriceEditable: true,
                                                                        maxQuantity: {{ $remainingQty }}
                                                                    })"
                                                                    :disabled="isSelected({{ $item->id }}) || !{{ $isAvailable ? 'true' : 'false' }}"
                                                                    class="px-3 py-1 text-xs rounded transition-colors {{ $isAvailable ? 'bg-indigo-600 text-white hover:bg-indigo-700' : 'bg-gray-300 text-gray-500 cursor-not-allowed' }} disabled:opacity-50 disabled:cursor-not-allowed">
                                                                    @if($isAvailable)
                                                                        <span x-show="!isSelected({{ $item->id }})">Add to PR</span>
                                                                        <span x-show="isSelected({{ $item->id }})">Added</span>
                                                                    @else
                                                                        <span>Not Available</span>
                                                                    @endif
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
                                        x-model="prDetails.purpose"
                                        required
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        placeholder="Enter purpose of procurement"
                                    />
                                </div>

                                <div>
                                    <label for="justification" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Justification <span class="text-red-500">*</span>
                                    </label>
                                    <textarea 
                                        id="justification" 
                                        name="justification"
                                        x-model="prDetails.justification"
                                        required
                                        rows="3"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        placeholder="Why is this procurement needed?"
                                    ></textarea>
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
                                                <div class="flex-1">
                                                    <input 
                                                        type="number" 
                                                        min="1"
                                                        :max="item.maxQuantity !== null && item.maxQuantity !== undefined ? item.maxQuantity : 999"
                                                        :value="item.quantity"
                                                        @input="updateQuantity(item.id, $event.target.value)"
                                                        @blur="updateQuantity(item.id, $event.target.value)"
                                                        class="w-20 text-xs rounded border-gray-300"
                                                    />
                                                    <span x-show="item.maxQuantity" class="text-xs text-gray-500 ml-1">
                                                        / <span x-text="item.maxQuantity"></span> max
                                                    </span>
                                                </div>
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
                                    Submit PR
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
                    purpose: '{{ old('purpose', '') }}',
                    justification: '{{ old('justification', '') }}'
                },
                ppmpItemLimits: @json($categorizedItems->flatten(1)->values()),

                init() {
                    // Load expanded state from localStorage
                    const stored = localStorage.getItem('prExpandedCategories');
                    if (stored) {
                        this.expandedCategories = JSON.parse(stored);
                    }
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
                           this.prDetails.purpose.trim() !== '' &&
                           this.prDetails.justification.trim() !== '';
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

                    // Check if item is available for current quarter
                    const itemLimit = this.ppmpItemLimits.find(i => i.item && i.item.id === itemId);
                    if (!itemLimit || itemLimit.remainingQty <= 0) {
                        alert('This item is not available for the current quarter or has no remaining quantity.');
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
                            maxQuantity: itemData.maxQuantity || itemLimit.remainingQty,
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
                    const itemLimit = this.ppmpItemLimits.find(i => i.item && i.item.id === this.priceModalItem.id);
                    
                    this.selectedItems.push({
                        id: Date.now(),
                        ppmpItemId: this.priceModalItem.id,
                        name: data.name,
                        code: data.code,
                        unit: data.unit,
                        price: price,
                        specs: data.specs,
                        quantity: 1,
                        maxQuantity: data.maxQuantity || (itemLimit ? itemLimit.remainingQty : 999),
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
                    if (!item) return;

                    let newQty = parseInt(value);
                    
                    // Ensure quantity is at least 1
                    if (isNaN(newQty) || newQty < 1) {
                        newQty = 1;
                    }
                    
                    // Validate against remaining PPMP quantity
                    // Check if maxQuantity is defined (not null/undefined) and is a valid number
                    if (item.maxQuantity !== null && item.maxQuantity !== undefined && typeof item.maxQuantity === 'number' && newQty > item.maxQuantity) {
                        alert(`Cannot exceed remaining quantity (${item.maxQuantity} available for current quarter)`);
                        // Reset to max quantity
                        item.quantity = item.maxQuantity;
                        return;
                    }
                    
                    item.quantity = newQty;
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
                        } else if (this.prDetails.justification.trim() === '') {
                            alert('Please enter the justification for the purchase request.');
                        }
                        return;
                    }

                    // Validate all quantities against max limits
                    for (const item of this.selectedItems) {
                        if (item.maxQuantity !== null && item.maxQuantity !== undefined && typeof item.maxQuantity === 'number' && item.quantity > item.maxQuantity) {
                            event.preventDefault();
                            alert(`Quantity for "${item.name}" (${item.quantity}) exceeds the maximum allowed quantity (${item.maxQuantity} available for current quarter).`);
                            // Reset to max quantity
                            item.quantity = item.maxQuantity;
                            return;
                        }
                    }

                    // Add hidden inputs for form submission
                    const form = event.target;
                    
                    // Remove old dynamic inputs
                    form.querySelectorAll('.dynamic-input').forEach(el => el.remove());

                    // Add purpose and justification from Alpine.js state
                    this.addHiddenInput(form, 'purpose', this.prDetails.purpose);
                    this.addHiddenInput(form, 'justification', this.prDetails.justification);

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
