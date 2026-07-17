{{--
    Expandable description for a quotable PR item (lot header or standalone).
    Expects: $item (PurchaseRequestItem), optional $buttonType ('button' default)
--}}
@if($item->isLotHeader())
    <button type="button"
            class="inline-flex items-center gap-1.5 text-left font-medium text-gray-900 hover:text-blue-700"
            @click.stop="open = !open"
            :aria-expanded="open.toString()">
        <svg class="w-4 h-4 text-gray-500 transition-transform shrink-0" :class="{ 'rotate-90': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
        </svg>
        <span>{{ $item->lot_name ?? $item->item_name }}</span>
        <span class="text-xs font-normal text-gray-500">({{ $item->lotChildren->count() }} items)</span>
    </button>
@else
    {{ $item->item_name }}
@endif
