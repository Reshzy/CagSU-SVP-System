<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Create/Edit PPMP') }} - FY {{ $fiscalYear }}
        </h2>
    </x-slot>

    <div class="py-12">
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

            <!-- Budget Status -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-bold text-gray-800 dark:text-gray-200">Budget Available</h3>
                        <div class="text-2xl font-bold">₱<span id="budgetAvailable">{{ number_format($budgetStatus['allocated'], 2) }}</span></div>
                    </div>
                    <div class="mt-2">
                        <span class="text-sm text-gray-600 dark:text-gray-400">PPMP Total: ₱<span id="ppmpTotal">0.00</span></span>
                    </div>
                </div>
            </div>

            <form action="{{ route('ppmp.store') }}" method="POST" id="ppmpForm">
                @csrf
                
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-bold mb-4 text-gray-800 dark:text-gray-200">Select Items from APP</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                            Select items and specify quantities per quarter. The system will calculate total costs automatically.
                        </p>

                        @foreach ($categories as $category)
                            <div class="mb-6">
                                <h4 class="text-md font-semibold mb-2 text-gray-800 dark:text-gray-200">{{ $category }}</h4>
                                <div class="space-y-2">
                                    @foreach ($appItems[$category] as $appItem)
                                        @php
                                            $existingItem = $existingItems->get($appItem->id);
                                        @endphp
                                        <div class="border border-gray-200 dark:border-gray-700 rounded p-3">
                                            <div class="grid grid-cols-12 gap-2 items-center">
                                                <div class="col-span-4">
                                                    <label class="flex items-center">
                                                        <input
                                                            type="checkbox"
                                                            name="selected_items[]"
                                                            value="{{ $appItem->id }}"
                                                            {{ $existingItem ? 'checked' : '' }}
                                                            class="item-checkbox rounded"
                                                            data-unit-price="{{ $appItem->unit_price }}"
                                                        />
                                                        <span class="ml-2 text-sm">
                                                            <strong>{{ $appItem->item_name }}</strong><br/>
                                                            <span class="text-xs text-gray-500">₱{{ number_format($appItem->unit_price, 2) }} / {{ $appItem->unit_of_measure }}</span>
                                                        </span>
                                                    </label>
                                                </div>
                                                <input type="hidden" name="items[{{ $loop->parent->index }}_{{ $loop->index }}][app_item_id]" value="{{ $appItem->id }}">
                                                <div class="col-span-2">
                                                    <label class="text-xs text-gray-600 dark:text-gray-400">Q1</label>
                                                    <input
                                                        type="number"
                                                        name="items[{{ $loop->parent->index }}_{{ $loop->index }}][q1_quantity]"
                                                        value="{{ $existingItem ? $existingItem->q1_quantity : 0 }}"
                                                        min="0"
                                                        class="qty-input w-full rounded text-sm"
                                                    />
                                                </div>
                                                <div class="col-span-2">
                                                    <label class="text-xs text-gray-600 dark:text-gray-400">Q2</label>
                                                    <input
                                                        type="number"
                                                        name="items[{{ $loop->parent->index }}_{{ $loop->index }}][q2_quantity]"
                                                        value="{{ $existingItem ? $existingItem->q2_quantity : 0 }}"
                                                        min="0"
                                                        class="qty-input w-full rounded text-sm"
                                                    />
                                                </div>
                                                <div class="col-span-2">
                                                    <label class="text-xs text-gray-600 dark:text-gray-400">Q3</label>
                                                    <input
                                                        type="number"
                                                        name="items[{{ $loop->parent->index }}_{{ $loop->index }}][q3_quantity]"
                                                        value="{{ $existingItem ? $existingItem->q3_quantity : 0 }}"
                                                        min="0"
                                                        class="qty-input w-full rounded text-sm"
                                                    />
                                                </div>
                                                <div class="col-span-2">
                                                    <label class="text-xs text-gray-600 dark:text-gray-400">Q4</label>
                                                    <input
                                                        type="number"
                                                        name="items[{{ $loop->parent->index }}_{{ $loop->index }}][q4_quantity]"
                                                        value="{{ $existingItem ? $existingItem->q4_quantity : 0 }}"
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
    </div>

    @push('scripts')
    <script>
        // Simple budget calculation
        document.querySelectorAll('.qty-input').forEach(input => {
            input.addEventListener('input', calculateTotal);
        });

        function calculateTotal() {
            let total = 0;
            document.querySelectorAll('.item-checkbox:checked').forEach(checkbox => {
                const row = checkbox.closest('.border');
                const unitPrice = parseFloat(checkbox.dataset.unitPrice);
                const quantities = row.querySelectorAll('.qty-input');
                let itemTotal = 0;
                quantities.forEach(qty => {
                    itemTotal += parseInt(qty.value || 0);
                });
                total += itemTotal * unitPrice;
            });
            document.getElementById('ppmpTotal').textContent = total.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        }

        calculateTotal();
    </script>
    @endpush
</x-app-layout>

