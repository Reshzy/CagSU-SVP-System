@section('title', 'Budget Office - Earmarking')

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-gray-800 leading-tight">{{ __('Budget Office - Earmark Requests') }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if(session('status'))
                        <div class="mb-4 p-3 rounded-md bg-green-50 text-green-700">{{ session('status') }}</div>
                    @endif

                    <div class="flex items-center justify-between mb-4">
                        <div class="text-sm text-gray-600">
                            @php
                                $from = $requests->firstItem();
                                $to = $requests->lastItem();
                                $total = $requests->total();
                            @endphp
                            Showing {{ $from }}–{{ $to }} of {{ $total }} pending earmarks
                        </div>
                        <div class="text-sm text-gray-600">
                            Pending: {{ $requests->total() }}
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">PR #</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Requester</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Purpose</th>
                                    <th class="px-4 py-2"></th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($requests as $req)
                                <tr>
                                    <td class="px-4 py-2 font-mono">{{ $req->pr_number }}</td>
                                    <td class="px-4 py-2">{{ $req->requester?->name }}</td>
                                    <td class="px-4 py-2">{{ $req->department?->name }}</td>
                                    <td class="px-4 py-2">{{ $req->purpose }}</td>
                                    <td class="px-4 py-2 text-right">
                                        <a href="{{ route('budget.purchase-requests.edit', $req) }}" class="px-3 py-2 bg-cagsu-yellow text-white rounded-md">Review</a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-6 text-center text-gray-500">No requests awaiting earmark.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">{{ $requests->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>


