@if($amendmentHistory->isNotEmpty())
    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Amendment History</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">All earmark changes, newest first</p>
        </div>

        @if($amendmentHistory->hasPages())
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                {{ $amendmentHistory->onEachSide(1)->links() }}
            </div>
        @endif

        <div class="divide-y divide-gray-100 dark:divide-gray-700">
            @foreach($amendmentHistory as $activity)
                <div class="px-6 py-4">
                    <div class="flex items-start gap-4">
                        <div class="mt-0.5 flex-shrink-0 p-2 rounded-full text-amber-600 bg-amber-100 dark:bg-amber-900/40 dark:text-amber-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-2 flex-wrap">
                                <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $activity->description }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ $activity->created_at->format('M d, Y g:i A') }}</p>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">by {{ $activity->user?->name ?? 'System' }}</p>
                            @if(is_array($activity->new_value) && count($activity->new_value))
                                <div class="mt-2 space-y-1">
                                    @foreach($activity->new_value as $field => $newVal)
                                        @php
                                            $oldVal = $activity->old_value[$field] ?? null;
                                            $label = ucwords(str_replace('_', ' ', $field));
                                            $displayOld = is_array($oldVal) ? json_encode($oldVal, JSON_UNESCAPED_UNICODE) : ($oldVal ?? '(empty)');
                                            $displayNew = is_array($newVal) ? json_encode($newVal, JSON_UNESCAPED_UNICODE) : ($newVal ?? '(empty)');
                                        @endphp
                                        <div class="flex items-start gap-2 text-xs">
                                            <span class="font-medium text-gray-600 dark:text-gray-400 min-w-0 shrink-0">{{ $label }}:</span>
                                            <span class="text-red-600 dark:text-red-400 line-through">{{ $displayOld }}</span>
                                            <svg class="w-3 h-3 text-gray-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                                            <span class="text-green-600 dark:text-green-400">{{ $displayNew }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if($amendmentHistory->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                {{ $amendmentHistory->onEachSide(1)->links() }}
            </div>
        @endif
    </div>
@else
    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Amendment History</h3>
        </div>
        <div class="px-6 py-10 text-center">
            <svg class="mx-auto h-10 w-10 text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <p class="text-sm text-gray-500 dark:text-gray-400">No amendments yet for this earmark.</p>
        </div>
    </div>
@endif
