<!-- Executive Officer Dashboard -->
@php
    $urgentApprovals = \App\Models\PurchaseRequest::where('status', 'ceo_approval')
        ->where('priority', 'urgent')
        ->count();

    $openPrValue = \App\Models\PurchaseRequest::whereIn('status', [
        'submitted','supply_office_review','budget_office_review','ceo_approval','bac_evaluation'
    ])->sum('estimated_total');

    $avgProcessDays = \App\Models\PurchaseRequest::whereNotNull('submitted_at')
        ->whereNotNull('completed_at')
        ->selectRaw('AVG(DATEDIFF(completed_at, submitted_at)) as avg_days')
        ->value('avg_days');

    $pendingCeoList = \App\Models\PurchaseRequest::with(['requester','department'])
        ->where('status','ceo_approval')
        ->orderByDesc('status_updated_at')
        ->limit(5)
        ->get();

    $recentExecActions = \App\Models\WorkflowApproval::with(['purchaseRequest','approvedBy'])
        ->where('step_name','ceo_initial_approval')
        ->whereNotNull('responded_at')
        ->orderByDesc('responded_at')
        ->limit(5)
        ->get();
@endphp
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
    
    <!-- Pending Executive Approvals -->
    <div class="bg-white overflow-hidden shadow-lg rounded-lg">
        <div class="p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-8 w-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Urgent Approvals</dt>
                        <dd class="text-lg font-medium text-red-600">{{ $urgentApprovals }}</dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="bg-gray-50 px-6 py-3">
            <div class="text-sm">
                <a href="{{ route('ceo.purchase-requests.index') }}" class="font-medium text-cagsu-maroon hover:text-cagsu-orange">Review now</a>
            </div>
        </div>
    </div>

    <!-- Budget Overview -->
    <div class="bg-white overflow-hidden shadow-lg rounded-lg">
        <div class="p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-8 w-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Open PR Value</dt>
                        <dd class="text-lg font-medium text-gray-900">₱{{ number_format((float)$openPrValue, 2) }}</dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="bg-gray-50 px-6 py-3">
            <div class="text-sm">
                <a href="#" class="font-medium text-cagsu-maroon hover:text-cagsu-orange">Budget details</a>
            </div>
        </div>
    </div>

    <!-- Procurement Efficiency -->
    <div class="bg-white overflow-hidden shadow-lg rounded-lg">
        <div class="p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-8 w-8 text-cagsu-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Avg. Process Time</dt>
                        <dd class="text-lg font-medium text-gray-900">{{ $avgProcessDays ? round($avgProcessDays, 1) . ' days' : '—' }}</dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="bg-gray-50 px-6 py-3">
            <div class="text-sm">
                <span class="text-green-600 font-medium">Target: 25 days</span>
            </div>
        </div>
    </div>

    <!-- Department Activity -->
    <div class="bg-white overflow-hidden shadow-lg rounded-lg">
        <div class="p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-8 w-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Active Departments</dt>
                        <dd class="text-lg font-medium text-gray-900">{{ \App\Models\Department::where('is_active', true)->count() }}</dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="bg-gray-50 px-6 py-3">
            <div class="text-sm">
                <a href="#" class="font-medium text-cagsu-maroon hover:text-cagsu-orange">View activity</a>
            </div>
        </div>
    </div>

</div>

<!-- Executive Summary -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    
    <!-- Priority Decisions -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg leading-6 font-medium text-gray-900 flex items-center">
                <svg class="w-5 h-5 text-red-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
                Priority Decisions Required
            </h3>
        </div>
        <div class="px-6 py-4">
            @if($pendingCeoList->isEmpty())
                <div class="text-center text-gray-500 py-8">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p class="mt-2 font-medium text-green-600">All caught up!</p>
                    <p class="text-sm">No approvals pending</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">PR #</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Requester</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                                <th class="px-4 py-2"></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($pendingCeoList as $req)
                            <tr>
                                <td class="px-4 py-2 font-mono">{{ $req->pr_number }}</td>
                                <td class="px-4 py-2">{{ $req->requester?->name }}</td>
                                <td class="px-4 py-2">{{ $req->department?->name }}</td>
                                <td class="px-4 py-2 text-right">
                                    <a href="{{ route('ceo.purchase-requests.show', $req) }}" class="px-3 py-2 bg-cagsu-maroon text-white rounded-md">Review</a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <!-- Recent Executive Actions -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Recent Executive Actions</h3>
        </div>
        <div class="px-6 py-4">
            @if($recentExecActions->isEmpty())
                <div class="text-center text-gray-500 py-8">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 012-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                    <p class="mt-2">No recent actions</p>
                </div>
            @else
                <ul class="divide-y divide-gray-200">
                    @foreach($recentExecActions as $act)
                        <li class="py-3 text-sm flex items-center justify-between">
                            <div>
                                <div class="font-medium">{{ $act->purchaseRequest?->pr_number }}</div>
                                <div class="text-gray-500">{{ $act->status === 'rejected' ? 'Deferred' : \Illuminate\Support\Str::title(str_replace('_',' ', $act->status)) }} • {{ optional($act->responded_at)->diffForHumans() }}</div>
                            </div>
                            <div class="text-gray-600">by {{ $act->approvedBy?->name ?? '—' }}</div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

</div>