<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            CEO • Review User
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <a href="{{ route('ceo.users.index') }}" class="text-sm underline">Back to list</a>
                    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h3 class="text-lg font-medium mb-2">User Info</h3>
                            <dl class="text-sm">
                                <dt class="font-medium">Name</dt>
                                <dd class="mb-2">{{ $user->name }}</dd>
                                <dt class="font-medium">Email</dt>
                                <dd class="mb-2">{{ $user->email }}</dd>
                                <dt class="font-medium">Department</dt>
                                <dd class="mb-2">{{ optional($user->department)->name ?? '—' }}</dd>
                                <dt class="font-medium">Employee ID</dt>
                                <dd class="mb-2">{{ $user->employee_id ?? '—' }}</dd>
                                <dt class="font-medium">Position</dt>
                                <dd class="mb-2">{{ $user->position?->name ?? '—' }}</dd>
                                <dt class="font-medium">Phone</dt>
                                <dd class="mb-2">{{ $user->phone ?? '—' }}</dd>
                                <dt class="font-medium">Status</dt>
                                <dd class="mb-2">{{ ucfirst($user->approval_status) }}</dd>
                            </dl>
                        </div>
                        <div>
                            <h3 class="text-lg font-medium mb-2">Submitted Documents</h3>
                            <ul class="space-y-2">
                                @forelse($user->documents as $doc)
                                    <li class="flex items-center justify-between">
                                        <span>{{ $doc->title }} ({{ strtoupper($doc->file_extension) }})</span>
                                        <a href="{{ route('files.show', $doc) }}" target="_blank" class="px-3 py-1 border rounded">View</a>
                                    </li>
                                @empty
                                    <li>No documents uploaded.</li>
                                @endforelse
                            </ul>
                        </div>
                    </div>

                    <div class="mt-6 flex gap-3">
                        <form method="POST" action="{{ route('ceo.users.approve', $user) }}">
                            @csrf
                            <button class="px-4 py-2 bg-green-600 text-white rounded">Approve</button>
                        </form>
                        <form method="POST" action="{{ route('ceo.users.reject', $user) }}">
                            @csrf
                            <button class="px-4 py-2 bg-red-600 text-white rounded">Defer</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>


