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
                            <p>{{ $activity->description }}</p>

                            @if($activity->action === 'returned' && isset($activity->new_value['return_remarks']))
                            <div class="mt-2 p-3 bg-yellow-50 border border-yellow-200 rounded-md">
                                <p class="text-xs font-semibold text-yellow-900 mb-1">Remarks:</p>
                                <p class="text-sm text-yellow-900">{{ $activity->new_value['return_remarks'] }}</p>
                            </div>
                            @endif

                            @if($activity->action === 'rejected' && isset($activity->new_value['rejection_reason']))
                            <div class="mt-2 p-3 bg-red-50 border border-red-200 rounded-md">
                                <p class="text-xs font-semibold text-red-900 mb-1">Reason:</p>
                                <p class="text-sm text-red-900">{{ $activity->new_value['rejection_reason'] }}</p>
                            </div>
                            @endif

                            @if($activity->action === 'notes_added' && isset($activity->new_value['notes']))
                            <div class="mt-2 p-3 bg-blue-50 border border-blue-200 rounded-md">
                                <p class="text-sm text-blue-900">{{ $activity->new_value['notes'] }}</p>
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

