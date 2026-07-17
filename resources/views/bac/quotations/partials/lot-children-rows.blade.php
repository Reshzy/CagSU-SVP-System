{{--
    Read-only child rows for an expanded lot.
    Expects: $item (lot header), $mode = summary|pricing|comparison
    Optional for comparison: $supplierCount
--}}
@php
    $mode = $mode ?? 'summary';
    $supplierCount = $supplierCount ?? 0;
@endphp
@if($item->isLotHeader())
    @foreach($item->lotChildren as $child)
        <tr x-show="open" x-cloak class="bg-slate-50 text-gray-600">
            @if($mode === 'comparison')
                <td class="px-3 py-2 border-r pl-6 text-sm">{{ $child->item_name }}</td>
                <td class="px-3 py-2 text-center border-r text-sm">{{ $child->unit_of_measure }}</td>
                <td class="px-3 py-2 text-center border-r text-sm">{{ $child->quantity_requested }}</td>
                <td class="px-3 py-2 text-right font-mono border-r text-sm">₱{{ number_format((float) $child->estimated_unit_cost, 2) }}</td>
                @for($i = 0; $i < $supplierCount; $i++)
                    <td class="px-3 py-2 text-center text-xs text-gray-400 border-l">—</td>
                @endfor
            @elseif($mode === 'pricing')
                <td class="px-4 py-2 text-sm pl-8">{{ $child->quantity_requested }}</td>
                <td class="px-4 py-2 text-sm">{{ $child->unit_of_measure }}</td>
                <td class="px-4 py-2 text-sm">{{ $child->item_name }}</td>
                <td class="px-4 py-2 text-sm text-right font-mono">₱{{ number_format((float) $child->estimated_unit_cost, 2) }}</td>
                <td colspan="3" class="px-4 py-2 text-sm text-center text-xs text-gray-400">Included in lot bid</td>
            @else
                <td class="px-4 py-2 text-sm pl-8">{{ $child->quantity_requested }}</td>
                <td class="px-4 py-2 text-sm">{{ $child->unit_of_measure }}</td>
                <td class="px-4 py-2 text-sm">{{ $child->item_name }}</td>
                <td class="px-4 py-2 text-sm text-right font-mono">₱{{ number_format((float) $child->estimated_unit_cost, 2) }}</td>
                <td class="px-4 py-2 text-sm text-right font-mono">₱{{ number_format((float) $child->estimated_total_cost, 2) }}</td>
                @if($mode === 'summary-status')
                    <td class="px-4 py-2 text-sm text-center text-xs text-gray-400">Included in lot</td>
                @endif
            @endif
        </tr>
    @endforeach
@endif
