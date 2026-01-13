<!-- End User Dashboard -->
@php
    $myCount = \App\Models\PurchaseRequest::where('requester_id', Auth::id())->count();
    $pendingCount = \App\Models\PurchaseRequest::where('requester_id', Auth::id())
        ->whereNotIn('status', ['completed','cancelled','rejected'])
        ->count();
    $completedCount = \App\Models\PurchaseRequest::where('requester_id', Auth::id())
        ->where('status','completed')
        ->count();
    $recent = \App\Models\PurchaseRequest::where('requester_id', Auth::id())
        ->latest()->take(5)->get();
@endphp
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
    
    <!-- My Purchase Requests -->
    <div class="bg-white overflow-hidden shadow-lg rounded-lg">
        <div class="p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-8 w-8 text-cagsu-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">My Purchase Requests</dt>
                        <dd class="text-lg font-medium text-gray-900">{{ $myCount }}</dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="bg-gray-50 px-6 py-3">
            <div class="text-sm">
                <a href="{{ route('purchase-requests.index') }}" class="font-medium text-cagsu-maroon hover:text-cagsu-orange">View all requests</a>
            </div>
        </div>
    </div>

    <!-- Pending Requests -->
    <div class="bg-white overflow-hidden shadow-lg rounded-lg">
        <div class="p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-8 w-8 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Pending Approval</dt>
                        <dd class="text-lg font-medium text-gray-900">{{ $pendingCount }}</dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="bg-gray-50 px-6 py-3">
            <div class="text-sm">
                <a href="{{ route('purchase-requests.index') }}" class="font-medium text-cagsu-maroon hover:text-cagsu-orange">Track status</a>
            </div>
        </div>
    </div>

    <!-- Completed Requests -->
    <div class="bg-white overflow-hidden shadow-lg rounded-lg">
        <div class="p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-8 w-8 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Completed</dt>
                        <dd class="text-lg font-medium text-gray-900">{{ $completedCount }}</dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="bg-gray-50 px-6 py-3">
            <div class="text-sm">
                <a href="{{ route('purchase-requests.index') }}" class="font-medium text-cagsu-maroon hover:text-cagsu-orange">View history</a>
            </div>
        </div>
    </div>

    <!-- PPMP Management -->
    @if(auth()->user()->department_id)
        <div class="bg-white overflow-hidden shadow-lg rounded-lg">
            <div class="p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-8 w-8 text-cagsu-maroon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">My PPMP</dt>
                            @php
                                $ppmp = \App\Models\Ppmp::where('department_id', auth()->user()->department_id)
                                    ->where('fiscal_year', date('Y'))
                                    ->first();
                            @endphp
                            <dd class="text-lg font-medium text-gray-900">{{ $ppmp ? ucfirst($ppmp->status) : 'Not Created' }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-6 py-3">
                <div class="text-sm">
                    <a href="{{ route('ppmp.index') }}" class="font-medium text-cagsu-maroon hover:text-cagsu-orange">Manage PPMP</a>
                </div>
            </div>
        </div>
    @endif

</div>

<!-- Recent Activity -->
<div class="mt-6">
    <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Recent Activity</h3>
        </div>
        <div class="px-6 py-4">
            @if($recent->count() > 0)
                <ul class="divide-y divide-gray-200">
                    @foreach($recent as $pr)
                        <li class="py-3 flex items-center justify-between">
                            <div>
                                <div class="text-sm font-medium text-gray-900">{{ $pr->pr_number ?? ('PR #' . $pr->id) }}</div>
                                <div class="text-xs text-gray-500">{{ optional($pr->created_at)->format('Y-m-d H:i') }} • {{ $pr->status === 'rejected' ? 'Deferred' : ucfirst(str_replace('_',' ', $pr->status)) }}</div>
                            </div>
                            <a href="{{ route('purchase-requests.index') }}" class="text-cagsu-maroon hover:text-cagsu-orange text-sm">View</a>
                        </li>
                    @endforeach
                </ul>
            @else
                <div class="text-center text-gray-500 py-8">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                    <p class="mt-2">No purchase requests yet</p>
                    <p class="text-sm">Create your first purchase request to get started</p>
                    <a href="{{ route('purchase-requests.create') }}" class="mt-4 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-cagsu-maroon hover:bg-cagsu-orange">
                        Create Purchase Request
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>