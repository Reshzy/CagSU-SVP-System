<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            CEO • Department Requests
            @if($pendingCount > 0)
                <span class="ml-2 inline-flex items-center rounded-full bg-cagsu-yellow px-2.5 py-0.5 text-xs font-semibold text-cagsu-maroon">
                    {{ $pendingCount }} pending
                </span>
            @endif
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    @if(session('status'))
                        <div class="mb-4 rounded-md bg-green-50 px-4 py-3 text-sm text-green-700">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="mb-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-700">
                            {{ session('error') }}
                        </div>
                    @endif

                    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                        <h3 class="text-lg font-medium">Department Requests</h3>
                        <form method="GET" action="{{ route('ceo.department-requests.index') }}" class="flex items-center gap-2">
                            <label for="status" class="text-sm font-medium text-gray-700">Status:</label>
                            <select id="status" name="status" onchange="this.form.submit()" class="rounded-md border-gray-300 py-1 text-sm shadow-sm focus:border-cagsu-maroon focus:ring-cagsu-maroon">
                                <option value="pending" @selected($status === 'pending')>Pending</option>
                                <option value="approved" @selected($status === 'approved')>Approved</option>
                                <option value="rejected" @selected($status === 'rejected')>Rejected</option>
                                <option value="" @selected($status === '')>All</option>
                            </select>
                        </form>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-left text-sm">
                            <thead>
                                <tr class="border-b bg-gray-50">
                                    <th class="px-3 py-2 font-semibold text-gray-700">Name</th>
                                    <th class="px-3 py-2 font-semibold text-gray-700">Code</th>
                                    <th class="px-3 py-2 font-semibold text-gray-700">Requester Email</th>
                                    <th class="px-3 py-2 font-semibold text-gray-700">Status</th>
                                    <th class="px-3 py-2 font-semibold text-gray-700">Submitted</th>
                                    <th class="px-3 py-2"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($departmentRequests as $dr)
                                    <tr class="border-b hover:bg-gray-50">
                                        <td class="px-3 py-2 font-medium">{{ $dr->name }}</td>
                                        <td class="px-3 py-2">
                                            <span class="rounded bg-gray-100 px-1.5 py-0.5 font-mono text-xs">{{ strtoupper($dr->code) }}</span>
                                        </td>
                                        <td class="px-3 py-2 text-gray-600">{{ $dr->requester_email ?? '—' }}</td>
                                        <td class="px-3 py-2">
                                            @if($dr->status === 'pending')
                                                <span class="inline-flex items-center rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-semibold text-yellow-800">Pending</span>
                                            @elseif($dr->status === 'approved')
                                                <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-800">Approved</span>
                                            @else
                                                <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-semibold text-red-800">Rejected</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 text-gray-500">{{ $dr->created_at->diffForHumans() }}</td>
                                        <td class="px-3 py-2 text-right">
                                            <a href="{{ route('ceo.department-requests.show', $dr) }}" class="inline-flex items-center rounded border border-gray-300 px-3 py-1 text-xs font-medium text-gray-700 hover:bg-gray-50">
                                                Review
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="px-3 py-6 text-center text-gray-500" colspan="6">No department requests found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">{{ $departmentRequests->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
