{{--
    Selectable quotable PR item for item-group create/edit.
    Expects: $item (PurchaseRequestItem), $inputName (e.g. groups[0][items][]), optional $checked (bool)
--}}
@php
    $checked = $checked ?? false;
    $displayName = $item->isLotHeader()
        ? ($item->lot_name ?? $item->item_name)
        : $item->item_name;
@endphp
<div class="p-2 hover:bg-white rounded" @if($item->isLotHeader()) x-data="{ open: false }" @endif>
    <div class="flex items-start">
        <label class="flex items-start flex-1 cursor-pointer min-w-0">
            <input
                type="checkbox"
                name="{{ $inputName }}"
                value="{{ $item->id }}"
                class="mt-1 mr-3 item-checkbox"
                data-item-id="{{ $item->id }}"
                @checked($checked)
            >
            <div class="flex-1 min-w-0">
                <div class="font-medium text-gray-900">{{ $displayName }}</div>
                <div class="text-sm text-gray-600">
                    Qty: {{ $item->quantity_requested }} {{ $item->unit_of_measure }} |
                    ABC: ₱{{ number_format((float) $item->estimated_total_cost, 2) }}
                    @if($item->isLotHeader())
                        <span class="text-gray-500">({{ $item->lotChildren->count() }} items)</span>
                    @endif
                </div>
            </div>
        </label>
        @if($item->isLotHeader() && $item->lotChildren->isNotEmpty())
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
        @endif
    </div>
    @if($item->isLotHeader() && $item->lotChildren->isNotEmpty())
        <div x-show="open" x-cloak class="mt-2 ml-8 space-y-1 border-l-2 border-indigo-100 pl-3">
            @foreach($item->lotChildren->sortBy('id') as $child)
                <div class="text-sm text-gray-700">
                    <span class="font-medium">{{ $child->item_name }}</span>
                    <span class="text-gray-500">
                        — Qty: {{ $child->quantity_requested }} {{ $child->unit_of_measure }} |
                        ABC: ₱{{ number_format((float) $child->estimated_unit_cost, 2) }}
                    </span>
                </div>
            @endforeach
        </div>
    @endif
</div>
