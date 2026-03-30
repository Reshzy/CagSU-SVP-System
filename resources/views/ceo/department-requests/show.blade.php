<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            CEO • Review Department Request
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4">
                <a href="{{ route('ceo.department-requests.index') }}" class="inline-flex items-center gap-1 text-sm text-cagsu-maroon underline-offset-4 hover:underline">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Back to department requests
                </a>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    @if(session('status'))
                        <div class="mb-4 rounded-md bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('status') }}</div>
                    @endif

                    @if(session('error'))
                        <div class="mb-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
                    @endif

                    <div class="mb-6 flex items-start justify-between gap-4">
                        <div>
                            <h3 class="text-xl font-semibold text-gray-900">{{ $departmentRequest->name }}</h3>
                            <span class="font-mono text-sm text-gray-500">{{ strtoupper($departmentRequest->code) }}</span>
                        </div>

                        @if($departmentRequest->status === 'pending')
                            <span class="inline-flex items-center rounded-full bg-yellow-100 px-3 py-1 text-sm font-semibold text-yellow-800">Pending Review</span>
                        @elseif($departmentRequest->status === 'approved')
                            <span class="inline-flex items-center rounded-full bg-green-100 px-3 py-1 text-sm font-semibold text-green-800">Approved</span>
                        @else
                            <span class="inline-flex items-center rounded-full bg-red-100 px-3 py-1 text-sm font-semibold text-red-800">Rejected</span>
                        @endif
                    </div>

                    <dl class="divide-y divide-gray-100 text-sm">
                        <div class="flex gap-4 py-3">
                            <dt class="w-40 font-medium text-gray-600 shrink-0">Department Name</dt>
                            <dd class="text-gray-900">{{ $departmentRequest->name }}</dd>
                        </div>
                        <div class="flex gap-4 py-3">
                            <dt class="w-40 font-medium text-gray-600 shrink-0">Short Code</dt>
                            <dd class="font-mono text-gray-900">{{ strtoupper($departmentRequest->code) }}</dd>
                        </div>
                        <div class="flex gap-4 py-3">
                            <dt class="w-40 font-medium text-gray-600 shrink-0">Description</dt>
                            <dd class="text-gray-900">{{ $departmentRequest->description ?: '—' }}</dd>
                        </div>
                        <div class="flex gap-4 py-3">
                            <dt class="w-40 font-medium text-gray-600 shrink-0">Department Head</dt>
                            <dd class="text-gray-900">{{ $departmentRequest->head_name ?: '—' }}</dd>
                        </div>
                        <div class="flex gap-4 py-3">
                            <dt class="w-40 font-medium text-gray-600 shrink-0">Head Contact Email</dt>
                            <dd class="text-gray-900">{{ $departmentRequest->contact_email ?: '—' }}</dd>
                        </div>
                        <div class="flex gap-4 py-3">
                            <dt class="w-40 font-medium text-gray-600 shrink-0">Head Contact Phone</dt>
                            <dd class="text-gray-900">{{ $departmentRequest->contact_phone ?: '—' }}</dd>
                        </div>
                        <div class="flex gap-4 py-3">
                            <dt class="w-40 font-medium text-gray-600 shrink-0">Requester Email</dt>
                            <dd class="text-gray-900">{{ $departmentRequest->requester_email ?: '—' }}</dd>
                        </div>
                        <div class="flex gap-4 py-3">
                            <dt class="w-40 font-medium text-gray-600 shrink-0">Submitted</dt>
                            <dd class="text-gray-900">{{ $departmentRequest->created_at->format('M d, Y g:i A') }} ({{ $departmentRequest->created_at->diffForHumans() }})</dd>
                        </div>
                        @if(! $departmentRequest->isPending())
                            <div class="flex gap-4 py-3">
                                <dt class="w-40 font-medium text-gray-600 shrink-0">Reviewed By</dt>
                                <dd class="text-gray-900">{{ optional($departmentRequest->reviewer)->name ?? '—' }}</dd>
                            </div>
                            <div class="flex gap-4 py-3">
                                <dt class="w-40 font-medium text-gray-600 shrink-0">Reviewed At</dt>
                                <dd class="text-gray-900">{{ optional($departmentRequest->reviewed_at)->format('M d, Y g:i A') ?? '—' }}</dd>
                            </div>
                            @if($departmentRequest->isRejected())
                                <div class="flex gap-4 py-3">
                                    <dt class="w-40 font-medium text-gray-600 shrink-0">Rejection Reason</dt>
                                    <dd class="text-red-700">{{ $departmentRequest->rejection_reason }}</dd>
                                </div>
                            @endif
                        @endif
                    </dl>

                    @if($departmentRequest->isPending())
                        <div class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-2">

                            {{-- Approve --}}
                            <form method="POST" action="{{ route('ceo.department-requests.approve', $departmentRequest) }}">
                                @csrf
                                <button
                                    type="submit"
                                    onclick='return confirm("Approve and create the department \"{{ addslashes($departmentRequest->name) }}\"?")'
                                    class="w-full rounded-md bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
                                >
                                    Approve & Create Department
                                </button>
                            </form>

                            {{-- Reject --}}
                            <div x-data="{ open: false }">
                                <button
                                    @click="open = true"
                                    type="button"
                                    class="w-full rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
                                >
                                    Reject Request
                                </button>

                                <div
                                    x-show="open"
                                    x-cloak
                                    class="mt-4"
                                >
                                    <form method="POST" action="{{ route('ceo.department-requests.reject', $departmentRequest) }}">
                                        @csrf
                                        <div>
                                            <label for="rejection_reason" class="block text-sm font-medium text-gray-700">Reason for rejection <span class="text-red-600">*</span></label>
                                            <textarea
                                                id="rejection_reason"
                                                name="rejection_reason"
                                                rows="3"
                                                required
                                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-cagsu-maroon focus:ring-cagsu-maroon text-sm"
                                                placeholder="Provide a reason so the requester understands why their request was declined…"
                                            >{{ old('rejection_reason') }}</textarea>
                                            @error('rejection_reason')
                                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        <div class="mt-3 flex gap-2">
                                            <button type="submit" class="rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">
                                                Confirm Rejection
                                            </button>
                                            <button @click="open = false" type="button" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                                                Cancel
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
