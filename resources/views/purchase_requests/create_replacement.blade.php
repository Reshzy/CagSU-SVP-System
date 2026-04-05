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

            <!-- Grace Period Banner (if active) -->
            @if($gracePeriodInfo && $gracePeriodInfo['active'])
            <div class="bg-green-50 dark:bg-green-900 border-l-4 border-green-500 p-4 mb-6 rounded-r-lg {{ $gracePeriodInfo['expiring_soon'] ? 'animate-pulse' : '' }}">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-3 flex-1">
                        <h3 class="text-lg font-medium text-green-800 dark:text-green-200">
                            Grace Period Active
                        </h3>
                        <p class="text-sm text-green-700 dark:text-green-300 mt-1">
                            You can select items from <strong>Q{{ $gracePeriodInfo['quarter'] }} ({{ $gracePeriodInfo['quarter_label'] }})</strong> 
                            until <strong>{{ $gracePeriodInfo['end_date_formatted'] }}</strong>
                            <span class="font-semibold">({{ $gracePeriodInfo['days_remaining'] }} {{ $gracePeriodInfo['days_remaining'] == 1 ? 'day' : 'days' }} remaining)</span>
                        </p>
                        @if($gracePeriodInfo['expiring_soon'])
                        <p class="text-sm text-green-800 dark:text-green-200 mt-2 font-semibold">
                            ⚠️ Grace period expires soon! Submit your replacement PR before {{ $gracePeriodInfo['end_date_formatted'] }}.
                        </p>
                        @endif
                    </div>
                </div>
            </div>
            @endif

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
                                    <div class="flex items-center gap-4 text-sm text-gray-600 dark:text-gray-400">
                                        <button
                                            type="button"
                                            @click="closeAllCategories"
                                            class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-indigo-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:focus-visible:ring-offset-gray-800"
                                        >
                                            Collapse all
                                        </button>
                                        <div>
                                            <span x-text="selectedCount"></span> items selected
                                        </div>
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
                                    $allCategoriesForSearch = [];
                                    foreach (array_merge($mainCategories, $part2Categories) as $catName => $items) {
                                        $allCategoriesForSearch[] = [
                                            'name' => $catName,
                                            'itemNames' => $items->pluck('appItem.item_name')->toArray(),
                                            'itemCodes' => $items->pluck('appItem.item_code')->toArray(),
                                        ];
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
                                    
                                    // Get quarter status for this item
                                    $itemData = $categorizedItems[$category]->firstWhere('item.id', $item->id);
                                    $quarterStatus = $itemData['quarterStatus'] ?? 'unavailable';
                                    $remainingQty = $itemData['remainingQty'] ?? 0;
                                    $currentQuarterQty = $itemData['currentQuarterQty'] ?? 0;
                                    $isAvailable = ($quarterStatus === 'current' || $quarterStatus === 'grace_period') && $remainingQty > 0;
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
                                                @elseif($quarterStatus === 'grace_period' && $remainingQty > 0)
                                                    <span class="px-2 py-0.5 bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200 rounded text-xs font-medium flex items-center gap-1">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                        </svg>
                                                        Grace Period
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
                                            $isAvailable = ($quarterStatus === 'current' || $quarterStatus === 'grace_period') && $remainingQty > 0;
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
                                                        @elseif($quarterStatus === 'grace_period' && $remainingQty > 0)
                                                            <span class="px-2 py-0.5 bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200 rounded text-xs font-medium flex items-center gap-1">
                                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                                </svg>
                                                                Grace Period
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
                <div class="w-96 sticky top-[calc(var(--app-sticky-header-offset)+0.75rem)] self-start max-h-[calc(100vh-var(--app-sticky-header-offset)-0.75rem-6rem)] overflow-y-auto overscroll-y-contain scrollbar-hide space-y-6">
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
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-bold text-gray-800 dark:text-gray-200">Selected Items</h3>
                                <button
                                    type="button"
                                    x-show="ungroupedCount >= 2"
                                    @click="openLotModal"
                                    class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium bg-indigo-600 text-white rounded hover:bg-indigo-700">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                    Create Lot
                                </button>
                            </div>
                            
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

                            <div class="max-h-[32rem] overflow-y-auto space-y-2" x-show="selectedCount > 0">
                                <template x-for="item in displayItems" :key="item.id">
                                    <div>
                                        <template x-if="item.isLot">
                                            <div class="bg-indigo-50 dark:bg-indigo-900 p-3 rounded text-sm border border-indigo-300 dark:border-indigo-600">
                                                <div class="flex justify-between items-start mb-1">
                                                    <div class="flex-1">
                                                        <span class="inline-block text-xs font-bold bg-indigo-600 text-white px-1.5 py-0.5 rounded mr-1">LOT</span>
                                                        <input
                                                            type="text"
                                                            :value="item.lotName"
                                                            @input="updateLotName(item.id, $event.target.value)"
                                                            class="text-xs font-semibold bg-transparent border-b border-indigo-400 focus:outline-none focus:border-indigo-600 text-gray-800 dark:text-gray-100 w-40"
                                                            placeholder="Lot name..."
                                                        />
                                                    </div>
                                                    <div class="flex items-center gap-1 ml-2">
                                                        <button type="button" @click="editLot(item.id)" class="text-indigo-600 hover:text-indigo-800" title="Edit lot items">
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                        </button>
                                                        <button type="button" @click="ungroupLot(item.id)" class="text-orange-500 hover:text-orange-700" title="Ungroup lot">
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                                                        </button>
                                                    </div>
                                                </div>
                                                <template x-for="child in getLotChildren(item.id)" :key="child.id">
                                                    <div class="ml-3 mt-1 text-xs text-gray-600 dark:text-gray-400 flex items-center gap-1">
                                                        <span class="text-indigo-400">↳</span>
                                                        <span x-text="child.quantity + ' ' + child.unit + ', ' + child.name"></span>
                                                    </div>
                                                </template>
                                                <div class="flex items-center justify-between mt-2 pt-2 border-t border-indigo-200 dark:border-indigo-700">
                                                    <span class="text-xs text-gray-500">1 lot · unit cost = total</span>
                                                    <span class="text-xs font-semibold text-indigo-700 dark:text-indigo-300">₱<span x-text="formatNumber(item.price)"></span></span>
                                                </div>
                                            </div>
                                        </template>

                                        <template x-if="!item.isLot && !item.parentLotId">
                                            <div class="bg-gray-50 dark:bg-gray-900 p-3 rounded text-sm border border-gray-200 dark:border-gray-700">
                                                <div class="flex justify-between items-start mb-2">
                                                    <div class="flex-1 min-w-0">
                                                        <div class="text-xs text-gray-400 dark:text-gray-500 mb-0.5" x-show="item.originalName && item.originalName !== item.name" x-text="'Original: ' + item.originalName"></div>
                                                        <input
                                                            type="text"
                                                            :value="item.name"
                                                            @input="updateName(item.id, $event.target.value)"
                                                            class="w-full text-xs font-semibold bg-transparent border-b border-gray-300 dark:border-gray-600 focus:outline-none focus:border-indigo-500 text-gray-800 dark:text-gray-200"
                                                            placeholder="Item name..."
                                                        />
                                                        <div class="text-xs text-gray-500 mt-0.5" x-text="item.code"></div>
                                                    </div>
                                                    <button 
                                                        type="button"
                                                        @click="removeItem(item.id)"
                                                        class="text-red-500 hover:text-red-700 ml-2 flex-shrink-0">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                        </svg>
                                                    </button>
                                                </div>
                                                
                                                <div class="space-y-2">
                                                    <div class="flex items-center gap-2" x-show="item.isPriceEditable">
                                                        <label class="text-xs text-gray-600 dark:text-gray-400 w-16">Price:</label>
                                                        <div class="flex items-center flex-1">
                                                            <span class="text-xs mr-1 text-gray-600 dark:text-gray-400">₱</span>
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
                                                                @input="updateQuantity(item.id, $event.target.value, $event)"
                                                                @blur="updateQuantity(item.id, $event.target.value, $event)"
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
                                </template>
                            </div>

                            <div x-show="selectedCount === 0" class="text-center text-gray-500 text-sm py-8">
                                No items selected yet
                            </div>

                            <!-- Submit Button -->
                            <div class="mt-6 flex gap-2">
                                <a href="{{ route('purchase-requests.index') }}" 
                                   class="flex-1 flex items-center justify-center text-center px-4 py-2 bg-gray-500 hover:bg-gray-700 text-white font-bold rounded">
                                    Cancel
                                </a>
                                <button 
                                    type="submit"
                                    form="prForm"
                                    :disabled="!canSubmit"
                                    class="flex-1 flex items-center justify-center px-4 py-2 bg-blue-500 hover:bg-blue-700 text-white font-bold rounded disabled:opacity-50 disabled:cursor-not-allowed">
                                    Submit Replacement
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create / Edit Lot Modal -->
        <div x-show="showLotModal"
             x-cloak
             class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center"
             @click.self="closeLotModal">
            <div class="relative p-5 border w-[28rem] shadow-lg rounded-md bg-white dark:bg-gray-800 max-h-[90vh] overflow-y-auto">
                <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-1" x-text="editingLotId ? 'Edit Lot' : 'Create Lot'"></h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">Select items to bundle into one lot.</p>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Lot Name <span class="text-red-500">*</span></label>
                    <input type="text" x-model="lotModal.name" placeholder="e.g. Painting Works" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" @keydown.enter.prevent="" />
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Items to Include</label>
                    <div class="space-y-2 max-h-64 overflow-y-auto">
                        <template x-for="item in lotCandidates" :key="item.id">
                            <label class="flex items-start gap-2 p-2 rounded border cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700" :class="lotModal.selectedIds.includes(item.id) ? 'border-indigo-400 bg-indigo-50 dark:bg-indigo-900' : 'border-gray-200 dark:border-gray-600'">
                                <input type="checkbox" :value="item.id" :checked="lotModal.selectedIds.includes(item.id)" @change="toggleLotItem(item.id)" class="mt-0.5 rounded border-gray-300 text-indigo-600" />
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm font-medium text-gray-800 dark:text-gray-200 truncate" x-text="item.name"></div>
                                    <div class="text-xs text-gray-500" x-text="item.quantity + ' ' + item.unit + ' · ₱' + formatNumber(item.price * item.quantity)"></div>
                                </div>
                            </label>
                        </template>
                    </div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 rounded p-3 mb-4 text-sm">
                    <div class="flex justify-between"><span class="text-gray-600 dark:text-gray-400">Items selected:</span><span class="font-medium" x-text="lotModal.selectedIds.length"></span></div>
                    <div class="flex justify-between mt-1"><span class="text-gray-600 dark:text-gray-400">Combined total:</span><span class="font-semibold text-indigo-700 dark:text-indigo-300">₱<span x-text="formatNumber(lotModalTotal)"></span></span></div>
                </div>
                <div class="flex gap-2 justify-end">
                    <button type="button" @click="closeLotModal" class="px-4 py-2 bg-gray-500 hover:bg-gray-700 text-white font-bold rounded text-sm">Cancel</button>
                    <button type="button" @click="saveLot" :disabled="lotModal.selectedIds.length < 2 || !lotModal.name.trim()" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded text-sm disabled:opacity-50 disabled:cursor-not-allowed" x-text="editingLotId ? 'Update Lot' : 'Create Lot'"></button>
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
                showLotModal: false,
                editingLotId: null,
                lotModal: { name: '', selectedIds: [] },
                priceModalItem: { id: null, name: '', customPrice: null, data: null },
                prDetails: {
                    purpose: '{{ old('purpose', $originalPr->purpose) }}',
                    justification: '{{ old('justification', $originalPr->justification) }}'
                },
                ppmpItemLimits: @json($categorizedItems->flatten(1)->values()),
                allCategoriesForSearch: @json($allCategoriesForSearch),

                init() {
                    const stored = localStorage.getItem('prExpandedCategories');
                    if (stored) {
                        this.expandedCategories = JSON.parse(stored);
                    }
                    this.$watch('searchQuery', (value) => {
                        const query = (value || '').toLowerCase().trim();
                        if (!query) { this.closeAllCategories(); return; }
                        const matchingCategories = [];
                        for (const cat of this.allCategoriesForSearch) {
                            const nameMatch = (cat.name || '').toLowerCase().includes(query);
                            const itemNameMatch = (cat.itemNames || []).some(n => n && String(n).toLowerCase().includes(query));
                            const itemCodeMatch = (cat.itemCodes || []).some(code => code && String(code).toLowerCase().includes(query));
                            if (nameMatch || itemNameMatch || itemCodeMatch) matchingCategories.push(cat.name);
                        }
                        this.expandedCategories = matchingCategories;
                        localStorage.setItem('prExpandedCategories', JSON.stringify(this.expandedCategories));
                    });

                    // Pre-populate with original PR items, preserving lot structure
                    @php
                        $originalLots = $originalPr->items->where('is_lot', true)->values();
                        $originalLineItems = $originalPr->items->where('is_lot', false)->values();
                    @endphp
                    (function() {
                        const idSeed = Date.now();
                        const lotIdMap = {};
                        @foreach($originalLots as $idx => $lot)
                        lotIdMap[{{ $lot->id }}] = idSeed + {{ $idx }};
                        this.selectedItems.push({
                            id: lotIdMap[{{ $lot->id }}],
                            ppmpItemId: null,
                            name: '{{ addslashes($lot->lot_name ?? $lot->item_name) }}',
                            originalName: '{{ addslashes($lot->lot_name ?? $lot->item_name) }}',
                            code: null,
                            unit: 'lot',
                            price: {{ $lot->estimated_unit_cost }},
                            specs: null,
                            quantity: 1,
                            maxQuantity: null,
                            isPriceEditable: false,
                            isLot: true,
                            lotName: '{{ addslashes($lot->lot_name ?? $lot->item_name) }}',
                            parentLotId: null,
                        });
                        @endforeach
                        @foreach($originalLineItems as $idx => $originalItem)
                        @php
                            $itemLimit = null;
                            if ($originalItem->ppmp_item_id) {
                                $itemLimit = $categorizedItems->flatten(1)->firstWhere('item.id', $originalItem->ppmp_item_id);
                            }
                            $maxQty = $itemLimit ? $itemLimit['remainingQty'] : 999;
                        @endphp
                        this.selectedItems.push({
                            id: idSeed + {{ $originalLots->count() + $idx }},
                            ppmpItemId: {{ $originalItem->ppmp_item_id ?? 'null' }},
                            name: '{{ addslashes($originalItem->item_name) }}',
                            originalName: '{{ addslashes($originalItem->item_name) }}',
                            code: '{{ $originalItem->item_code ?? '' }}',
                            unit: '{{ $originalItem->unit_of_measure }}',
                            price: {{ $originalItem->estimated_unit_cost }},
                            specs: '{{ addslashes($originalItem->detailed_specifications ?? '') }}',
                            quantity: {{ $originalItem->quantity_requested }},
                            maxQuantity: {{ $maxQty }},
                            isPriceEditable: {{ str_contains(strtoupper($originalItem->item_category ?? ''), 'SOFTWARE') || str_contains(strtoupper($originalItem->item_category ?? ''), 'PART') ? 'true' : 'false' }},
                            isLot: false,
                            lotName: null,
                            parentLotId: {{ $originalItem->parent_lot_id ? 'lotIdMap[' . $originalItem->parent_lot_id . ']' : 'null' }},
                        });
                        @endforeach
                    }).call(this);
                },

                get selectedCount() {
                    return this.selectedItems.length;
                },

                get prTotal() {
                    return this.selectedItems.filter(i => !i.parentLotId).reduce((sum, item) => sum + (item.price * item.quantity), 0);
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

                get displayItems() {
                    return this.selectedItems.filter(i => !i.parentLotId);
                },

                get ungroupedCount() {
                    return this.selectedItems.filter(i => !i.isLot && !i.parentLotId).length;
                },

                get lotCandidates() {
                    if (this.editingLotId) {
                        return this.selectedItems.filter(i => (!i.isLot && !i.parentLotId) || i.parentLotId === this.editingLotId);
                    }
                    return this.selectedItems.filter(i => !i.isLot && !i.parentLotId);
                },

                get lotModalTotal() {
                    return this.selectedItems.filter(i => this.lotModal.selectedIds.includes(i.id)).reduce((sum, i) => sum + i.price * i.quantity, 0);
                },

                getLotChildren(lotId) {
                    return this.selectedItems.filter(i => i.parentLotId === lotId);
                },

                isSelected(itemId) {
                    return this.selectedItems.some(item => item.ppmpItemId === itemId);
                },

                toggleCategory(category) {
                    const index = this.expandedCategories.indexOf(category);
                    if (index > -1) { this.expandedCategories.splice(index, 1); } else { this.expandedCategories.push(category); }
                    localStorage.setItem('prExpandedCategories', JSON.stringify(this.expandedCategories));
                },

                closeAllCategories() {
                    this.expandedCategories = [];
                    localStorage.setItem('prExpandedCategories', JSON.stringify([]));
                },

                addItem(itemId, itemData) {
                    if (this.isSelected(itemId)) return;
                    const itemLimit = this.ppmpItemLimits.find(i => i.item && i.item.id === itemId);
                    if (!itemLimit || itemLimit.remainingQty <= 0) {
                        alert('This item is not available for the current quarter or has no remaining quantity.');
                        return;
                    }
                    if (itemData.isPriceEditable) {
                        this.priceModalItem = { id: itemId, name: itemData.name, customPrice: itemData.price, data: itemData };
                        this.showPriceModal = true;
                    } else {
                        this.selectedItems.push({
                            id: Date.now(), ppmpItemId: itemId, name: itemData.name, originalName: itemData.name,
                            code: itemData.code, unit: itemData.unit, price: itemData.price, specs: itemData.specs,
                            quantity: 1, maxQuantity: itemData.maxQuantity || itemLimit.remainingQty,
                            isPriceEditable: false, isLot: false, lotName: null, parentLotId: null,
                        });
                    }
                },

                saveCustomPrice() {
                    const price = parseFloat(this.priceModalItem.customPrice);
                    if (!price || price <= 0) { alert('Please enter a valid price greater than 0'); return; }
                    const data = this.priceModalItem.data;
                    const itemLimit = this.ppmpItemLimits.find(i => i.item && i.item.id === this.priceModalItem.id);
                    this.selectedItems.push({
                        id: Date.now(), ppmpItemId: this.priceModalItem.id, name: data.name, originalName: data.name,
                        code: data.code, unit: data.unit, price: price, specs: data.specs,
                        quantity: 1, maxQuantity: data.maxQuantity || (itemLimit ? itemLimit.remainingQty : 999),
                        isPriceEditable: true, isLot: false, lotName: null, parentLotId: null,
                    });
                    this.closePriceModal();
                },

                closePriceModal() {
                    this.showPriceModal = false;
                    this.priceModalItem = { id: null, name: '', customPrice: null, data: null };
                },

                removeItem(itemId) {
                    const item = this.selectedItems.find(i => i.id === itemId);
                    if (!item) return;
                    if (item.isLot) {
                        this.selectedItems = this.selectedItems.filter(i => i.id !== itemId && i.parentLotId !== itemId);
                    } else {
                        this.selectedItems = this.selectedItems.filter(i => i.id !== itemId);
                    }
                },

                updateName(itemId, value) {
                    const item = this.selectedItems.find(i => i.id === itemId);
                    if (item) item.name = value;
                },

                updateLotName(lotId, value) {
                    const lot = this.selectedItems.find(i => i.id === lotId && i.isLot);
                    if (lot) { lot.lotName = value; lot.name = value; }
                },

                updateQuantity(itemId, value, evt) {
                    const item = this.selectedItems.find(i => i.id === itemId);
                    if (!item) return;
                    let newQty = parseInt(value);
                    if (isNaN(newQty) || newQty < 1) newQty = 1;
                    if (item.maxQuantity !== null && item.maxQuantity !== undefined && typeof item.maxQuantity === 'number' && newQty > item.maxQuantity) {
                        item.quantity = item.maxQuantity;
                        if (evt?.target) {
                            evt.target.value = String(item.maxQuantity);
                        }
                        if (evt?.type === 'input') {
                            alert(`Cannot exceed remaining quantity (${item.maxQuantity} available for current quarter)`);
                        }
                        return;
                    }
                    item.quantity = newQty;
                    if (evt?.target) {
                        evt.target.value = String(newQty);
                    }
                    if (item.parentLotId) this.recalculateLotPrice(item.parentLotId);
                },

                updatePrice(itemId, value) {
                    const item = this.selectedItems.find(i => i.id === itemId);
                    if (item && item.isPriceEditable) {
                        const newPrice = parseFloat(value);
                        if (!isNaN(newPrice) && newPrice >= 0) {
                            item.price = newPrice;
                            if (item.parentLotId) this.recalculateLotPrice(item.parentLotId);
                        }
                    }
                },

                recalculateLotPrice(lotId) {
                    const lot = this.selectedItems.find(i => i.id === lotId);
                    if (!lot) return;
                    lot.price = this.selectedItems.filter(i => i.parentLotId === lotId).reduce((sum, i) => sum + i.price * i.quantity, 0);
                },

                openLotModal() {
                    this.editingLotId = null;
                    this.lotModal = { name: '', selectedIds: [] };
                    this.showLotModal = true;
                },

                editLot(lotId) {
                    const lot = this.selectedItems.find(i => i.id === lotId && i.isLot);
                    if (!lot) return;
                    this.editingLotId = lotId;
                    this.lotModal = { name: lot.lotName || '', selectedIds: this.selectedItems.filter(i => i.parentLotId === lotId).map(i => i.id) };
                    this.showLotModal = true;
                },

                closeLotModal() {
                    this.showLotModal = false;
                    this.editingLotId = null;
                    this.lotModal = { name: '', selectedIds: [] };
                },

                toggleLotItem(itemId) {
                    const idx = this.lotModal.selectedIds.indexOf(itemId);
                    if (idx > -1) { this.lotModal.selectedIds.splice(idx, 1); } else { this.lotModal.selectedIds.push(itemId); }
                },

                saveLot() {
                    if (this.lotModal.selectedIds.length < 2 || !this.lotModal.name.trim()) return;
                    const lotName = this.lotModal.name.trim();
                    const selectedIds = [...this.lotModal.selectedIds];
                    if (this.editingLotId) {
                        const lot = this.selectedItems.find(i => i.id === this.editingLotId);
                        if (lot) {
                            lot.lotName = lotName; lot.name = lotName;
                            this.selectedItems.forEach(i => { if (i.parentLotId === this.editingLotId) i.parentLotId = null; });
                            selectedIds.forEach(id => { const child = this.selectedItems.find(i => i.id === id); if (child) child.parentLotId = this.editingLotId; });
                            this.recalculateLotPrice(this.editingLotId);
                        }
                    } else {
                        const lotTotal = this.selectedItems.filter(i => selectedIds.includes(i.id)).reduce((sum, i) => sum + i.price * i.quantity, 0);
                        const lotId = Date.now();
                        this.selectedItems.push({ id: lotId, ppmpItemId: null, name: lotName, originalName: lotName, lotName, code: null, unit: 'lot', price: lotTotal, specs: null, quantity: 1, maxQuantity: null, isPriceEditable: false, isLot: true, parentLotId: null });
                        selectedIds.forEach(id => { const child = this.selectedItems.find(i => i.id === id); if (child) child.parentLotId = lotId; });
                    }
                    this.closeLotModal();
                },

                ungroupLot(lotId) {
                    this.selectedItems.forEach(i => { if (i.parentLotId === lotId) i.parentLotId = null; });
                    this.selectedItems = this.selectedItems.filter(i => i.id !== lotId);
                },

                categoryVisible(category, itemNames, itemCodes) {
                    if (!this.searchQuery) return true;
                    const query = this.searchQuery.toLowerCase();
                    if (category.toLowerCase().includes(query)) return true;
                    return itemNames.some(name => name.toLowerCase().includes(query)) || itemCodes.some(code => code.toLowerCase().includes(query));
                },

                itemVisible(itemName, itemCode, category) {
                    if (!this.searchQuery) return true;
                    const query = this.searchQuery.toLowerCase();
                    return itemName.toLowerCase().includes(query) || itemCode.toLowerCase().includes(query) || category.toLowerCase().includes(query);
                },

                prepareSubmit(event) {
                    if (!this.canSubmit) {
                        event.preventDefault();
                        if (this.selectedCount === 0) alert('Please select at least one item.');
                        else if (this.budgetRemaining < 0) alert('Total cost exceeds available budget.');
                        else if (this.prDetails.purpose.trim() === '') alert('Please enter the purpose of the purchase request.');
                        else if (this.prDetails.justification.trim() === '') alert('Please enter the justification for the purchase request.');
                        return;
                    }
                    for (const item of this.selectedItems) {
                        if (item.isLot || item.parentLotId) continue;
                        if (item.maxQuantity !== null && item.maxQuantity !== undefined && typeof item.maxQuantity === 'number' && item.quantity > item.maxQuantity) {
                            event.preventDefault();
                            alert(`Quantity for "${item.name}" (${item.quantity}) exceeds the maximum allowed quantity (${item.maxQuantity} available for current quarter).`);
                            item.quantity = item.maxQuantity;
                            return;
                        }
                    }
                    const form = event.target;
                    form.querySelectorAll('.dynamic-input').forEach(el => el.remove());
                    this.addHiddenInput(form, 'purpose', this.prDetails.purpose);
                    this.addHiddenInput(form, 'justification', this.prDetails.justification);
                    const submissionOrder = [];
                    this.selectedItems.filter(i => i.isLot).forEach(lot => {
                        submissionOrder.push({ item: lot, parentIdx: null });
                        this.selectedItems.filter(i => i.parentLotId === lot.id).forEach(child => {
                            submissionOrder.push({ item: child, parentIdx: submissionOrder.findIndex(e => e.item.id === lot.id) });
                        });
                    });
                    this.selectedItems.filter(i => !i.isLot && !i.parentLotId).forEach(item => { submissionOrder.push({ item, parentIdx: null }); });
                    submissionOrder.forEach(({ item, parentIdx }, index) => {
                        this.addHiddenInput(form, `items[${index}][ppmp_item_id]`, item.ppmpItemId);
                        this.addHiddenInput(form, `items[${index}][item_code]`, item.code);
                        this.addHiddenInput(form, `items[${index}][item_name]`, item.name);
                        this.addHiddenInput(form, `items[${index}][unit_of_measure]`, item.isLot ? 'lot' : item.unit);
                        this.addHiddenInput(form, `items[${index}][detailed_specifications]`, item.specs);
                        this.addHiddenInput(form, `items[${index}][quantity_requested]`, item.isLot ? 1 : item.quantity);
                        this.addHiddenInput(form, `items[${index}][estimated_unit_cost]`, item.price);
                        this.addHiddenInput(form, `items[${index}][is_lot]`, item.isLot ? '1' : '0');
                        this.addHiddenInput(form, `items[${index}][lot_name]`, item.isLot ? item.lotName : '');
                        if (parentIdx !== null) this.addHiddenInput(form, `items[${index}][parent_lot_index]`, parentIdx);
                    });
                },

                addHiddenInput(form, name, value) {
                    const input = document.createElement('input');
                    input.type = 'hidden'; input.name = name; input.value = (value === undefined || value === null) ? '' : String(value); input.className = 'dynamic-input';
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
