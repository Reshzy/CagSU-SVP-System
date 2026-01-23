@props(['activities'])

<div class="flow-root">
    <ul role="list" class="-mb-8">
        @forelse($activities as $index => $activity)
        <li>
            <div class="relative pb-8">
                @if(!$loop->last)
                <span class="absolute left-5 top-5 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                @endif
                <div class="relative flex items-start space-x-3">
                    <div>
                        <div class="relative px-1">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full ring-8 ring-white {{ $activity->color_class }}">
                                {!! $activity->icon !!}
                            </div>
                        </div>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div>
                            <div class="text-sm">
                                @if($activity->user)
                                <a href="#" class="font-medium text-gray-900">{{ $activity->user->name }}</a>
                                @else
                                <span class="font-medium text-gray-500">System</span>
                                @endif
                            </div>
                            <p class="mt-0.5 text-sm text-gray-500">
                                {{ $activity->created_at->diffForHumans() }}
                                <span class="text-gray-400">•</span>
                                {{ $activity->created_at->format('M d, Y h:i A') }}
                            </p>
                        </div>
                        <div class="mt-2 text-sm text-gray-700">
                            <div class="flex items-center gap-2">
                                <p>{{ $activity->description }}</p>
                                @if($activity->pr_item_group_id && isset($activity->new_value['group_code']))
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-800">
                                    {{ $activity->new_value['group_code'] }}
                                </span>
                                @endif
                            </div>

                            @if($activity->action === 'returned' && isset($activity->new_value['return_remarks']))
                            <div class="mt-2 p-3 bg-yellow-50 border border-yellow-200 rounded-md">
                                <p class="text-xs font-semibold text-yellow-900 mb-1">Remarks:</p>
                                <p class="text-sm text-yellow-900">{{ $activity->new_value['return_remarks'] }}</p>
                            </div>
                            @endif

                            @if($activity->action === 'rejected' && isset($activity->new_value['rejection_reason']))
                            <div class="mt-2 p-3 bg-red-50 border border-red-200 rounded-md">
                                <p class="text-xs font-semibold text-red-900 mb-1">Deferral Reason:</p>
                                <p class="text-sm text-red-900">{{ $activity->new_value['rejection_reason'] }}</p>
                            </div>
                            @endif

                            @if($activity->action === 'notes_added' && isset($activity->new_value['notes']))
                            <div class="mt-2 p-3 bg-blue-50 border border-blue-200 rounded-md">
                                <p class="text-sm text-blue-900">{{ $activity->new_value['notes'] }}</p>
                            </div>
                            @endif

                            {{-- BAC Resolution generated/regenerated --}}
                            @if(in_array($activity->action, ['resolution_generated', 'resolution_regenerated']))
                            <div class="mt-2 p-3 bg-indigo-50 border border-indigo-200 rounded-md">
                                <div class="flex items-center gap-2">
                                    <svg class="h-4 w-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    <span class="text-sm font-medium text-indigo-900">BAC Resolution</span>
                                </div>
                                @if(isset($activity->new_value['resolution_number']))
                                <p class="mt-1 text-sm text-indigo-800">Reference: {{ $activity->new_value['resolution_number'] }}</p>
                                @endif
                                @if(isset($activity->new_value['procurement_method']))
                                <p class="text-xs text-indigo-600">Procurement Method: {{ ucwords(str_replace('_', ' ', $activity->new_value['procurement_method'])) }}</p>
                                @endif
                            </div>
                            @endif

                            {{-- RFQ generated --}}
                            @if($activity->action === 'rfq_generated')
                            <div class="mt-2 p-3 bg-purple-50 border border-purple-200 rounded-md">
                                <div class="flex items-center gap-2">
                                    <svg class="h-4 w-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                    </svg>
                                    <span class="text-sm font-medium text-purple-900">Request for Quotation</span>
                                </div>
                                @if(isset($activity->new_value['group_name']))
                                <p class="mt-1 text-sm text-purple-800">Group: {{ $activity->new_value['group_code'] }} - {{ $activity->new_value['group_name'] }}</p>
                                @endif
                            </div>
                            @endif

                            {{-- Quotation submitted --}}
                            @if($activity->action === 'quotation_submitted')
                            <div class="mt-2 p-3 bg-blue-50 border border-blue-200 rounded-md">
                                <div class="flex items-center gap-2">
                                    <svg class="h-4 w-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    <span class="text-sm font-medium text-blue-900">Quotation Submitted</span>
                                </div>
                                @if(isset($activity->new_value['supplier_name']))
                                <p class="mt-1 text-sm text-blue-800">Supplier: {{ $activity->new_value['supplier_name'] }}</p>
                                @endif
                                @if(isset($activity->new_value['total_amount']))
                                <p class="text-xs text-blue-600">Total: ₱{{ number_format($activity->new_value['total_amount'], 2) }}</p>
                                @endif
                            </div>
                            @endif

                            {{-- Quotation evaluated --}}
                            @if($activity->action === 'quotation_evaluated')
                            <div class="mt-2 p-3 {{ $activity->new_value['evaluation_status'] === 'responsive' ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200' }} border rounded-md">
                                <div class="flex items-center gap-2">
                                    <svg class="h-4 w-4 {{ $activity->new_value['evaluation_status'] === 'responsive' ? 'text-green-600' : 'text-red-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                                    </svg>
                                    <span class="text-sm font-medium {{ $activity->new_value['evaluation_status'] === 'responsive' ? 'text-green-900' : 'text-red-900' }}">
                                        {{ $activity->new_value['evaluation_status'] === 'responsive' ? 'Responsive' : 'Non-Responsive' }}
                                    </span>
                                </div>
                                @if(isset($activity->new_value['supplier_name']))
                                <p class="mt-1 text-sm {{ $activity->new_value['evaluation_status'] === 'responsive' ? 'text-green-800' : 'text-red-800' }}">
                                    Supplier: {{ $activity->new_value['supplier_name'] }}
                                </p>
                                @endif
                            </div>
                            @endif

                            {{-- AOQ generated --}}
                            @if($activity->action === 'aoq_generated')
                            <div class="mt-2 p-3 bg-teal-50 border border-teal-200 rounded-md">
                                <div class="flex items-center gap-2">
                                    <svg class="h-4 w-4 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                    </svg>
                                    <span class="text-sm font-medium text-teal-900">Abstract of Quotation</span>
                                </div>
                                @if(isset($activity->new_value['reference_number']))
                                <p class="mt-1 text-sm text-teal-800">Reference: {{ $activity->new_value['reference_number'] }}</p>
                                @endif
                                @if(isset($activity->new_value['group_name']))
                                <p class="text-xs text-teal-600">Group: {{ $activity->new_value['group_code'] }} - {{ $activity->new_value['group_name'] }}</p>
                                @endif
                            </div>
                            @endif

                            {{-- Tie resolved --}}
                            @if($activity->action === 'tie_resolved')
                            <div class="mt-2 p-3 bg-yellow-50 border border-yellow-200 rounded-md">
                                <div class="flex items-center gap-2">
                                    <svg class="h-4 w-4 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"></path>
                                    </svg>
                                    <span class="text-sm font-medium text-yellow-900">Tie Resolved</span>
                                </div>
                                @if(isset($activity->new_value['resolved_items']))
                                <p class="mt-1 text-xs text-yellow-800">{{ $activity->new_value['item_count'] }} item(s) resolved</p>
                                @endif
                            </div>
                            @endif

                            {{-- BAC override --}}
                            @if($activity->action === 'bac_override')
                            <div class="mt-2 p-3 bg-orange-50 border border-orange-200 rounded-md">
                                <div class="flex items-center gap-2">
                                    <svg class="h-4 w-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
                                    </svg>
                                    <span class="text-sm font-medium text-orange-900">BAC Override Applied</span>
                                </div>
                                @if(isset($activity->new_value['overridden_items']))
                                <p class="mt-1 text-xs text-orange-800">{{ $activity->new_value['item_count'] }} item(s) overridden</p>
                                @endif
                            </div>
                            @endif

                            {{-- Supplier withdrawal --}}
                            @if($activity->action === 'supplier_withdrawal')
                            <div class="mt-2 p-3 bg-red-50 border border-red-200 rounded-md">
                                <div class="flex items-center gap-2">
                                    <svg class="h-4 w-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7a4 4 0 11-8 0 4 4 0 018 0zM9 14a6 6 0 00-6 6v1h12v-1a6 6 0 00-6-6zM21 12h-6"></path>
                                    </svg>
                                    <span class="text-sm font-medium text-red-900">Supplier Withdrawal</span>
                                </div>
                                @if(isset($activity->new_value['supplier_name']))
                                <p class="mt-1 text-sm text-red-800">{{ $activity->new_value['supplier_name'] }}</p>
                                @endif
                                @if(isset($activity->new_value['withdrawal_reason']))
                                <p class="text-xs text-red-600">Reason: {{ $activity->new_value['withdrawal_reason'] }}</p>
                                @endif
                                @if(isset($activity->new_value['successor_supplier']))
                                <p class="mt-1 text-xs text-green-600">Winner succession to: {{ $activity->new_value['successor_supplier'] }}</p>
                                @endif
                            </div>
                            @endif

                            {{-- Item groups created/updated --}}
                            @if(in_array($activity->action, ['item_groups_created', 'item_groups_updated']))
                            <div class="mt-2 p-3 bg-slate-50 border border-slate-200 rounded-md">
                                <div class="flex items-center gap-2">
                                    <svg class="h-4 w-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                                    </svg>
                                    <span class="text-sm font-medium text-slate-900">
                                        {{ $activity->action === 'item_groups_created' ? 'Groups Created' : 'Groups Updated' }}
                                    </span>
                                </div>
                                @if(isset($activity->new_value['groups']))
                                <div class="mt-2 flex flex-wrap gap-1">
                                    @foreach($activity->new_value['groups'] as $group)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-200 text-slate-700">
                                        {{ $group['group_code'] }}: {{ $group['group_name'] }} ({{ $group['item_count'] }} items)
                                    </span>
                                    @endforeach
                                </div>
                                @endif
                            </div>
                            @endif

                            @if($activity->old_value || $activity->new_value)
                            <details class="mt-2">
                                <summary class="text-xs text-gray-500 cursor-pointer hover:text-gray-700">View details</summary>
                                <div class="mt-2 p-2 bg-gray-50 rounded text-xs font-mono">
                                    @if($activity->old_value)
                                    <div class="mb-2">
                                        <span class="font-semibold">Old:</span>
                                        <pre class="mt-1 text-xs">{{ json_encode($activity->old_value, JSON_PRETTY_PRINT) }}</pre>
                                    </div>
                                    @endif
                                    @if($activity->new_value)
                                    <div>
                                        <span class="font-semibold">New:</span>
                                        <pre class="mt-1 text-xs">{{ json_encode($activity->new_value, JSON_PRETTY_PRINT) }}</pre>
                                    </div>
                                    @endif
                                </div>
                            </details>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </li>
        @empty
        <li class="text-center py-8 text-gray-500">
            <svg class="mx-auto h-12 w-12 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <p class="text-sm">No activity history yet</p>
        </li>
        @endforelse
    </ul>
</div>

