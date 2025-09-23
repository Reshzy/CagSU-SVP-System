<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            CEO • User Management
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-medium mb-4">Users</h3>
                    <form method="GET" action="{{ route('ceo.users.index') }}" class="mb-4 grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                            <select id="status" name="status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">All</option>
                                <option value="pending" @selected(($status ?? null) === 'pending')>Pending</option>
                                <option value="approved" @selected(($status ?? null) === 'approved')>Approved</option>
                                <option value="rejected" @selected(($status ?? null) === 'rejected')>Rejected</option>
                            </select>
                        </div>
                        <div>
                            <label for="department_id" class="block text-sm font-medium text-gray-700">Department</label>
                            <select id="department_id" name="department_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">All</option>
                                @isset($departments)
                                    @foreach($departments as $dept)
                                        <option value="{{ $dept->id }}" @selected(($departmentId ?? null) == $dept->id)>{{ $dept->name }}</option>
                                    @endforeach
                                @endisset
                            </select>
                        </div>
                        <div class="md:col-span-2 flex gap-2">
                            <button class="px-4 py-2 border rounded" type="submit">Apply</button>
                            <a class="px-4 py-2 border rounded" href="{{ route('ceo.users.index', ['reset' => 1]) }}">Reset</a>
                        </div>
                    </form>
                    @if(session('status'))
                        <div class="mb-4 text-green-700">{{ session('status') }}</div>
                    @endif
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-left text-sm">
                            <thead>
                                <tr class="border-b">
                                    <th class="py-2">Name</th>
                                    <th class="py-2">Email</th>
                                    <th class="py-2">Department</th>
                                    <th class="py-2">Employee ID</th>
                                    <th class="py-2">Status</th>
                                    <th class="py-2">Registered</th>
                                    <th class="py-2"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($users as $user)
                                    <tr class="border-b">
                                        <td class="py-2">{{ $user->name }}</td>
                                        <td class="py-2">{{ $user->email }}</td>
                                        <td class="py-2">{{ optional($user->department)->name ?? '—' }}</td>
                                        <td class="py-2">{{ $user->employee_id ?? '—' }}</td>
                                        <td class="py-2">{{ ucfirst($user->approval_status) }}</td>
                                        <td class="py-2">{{ $user->created_at->diffForHumans() }}</td>
                                        <td class="py-2 text-right">
                                            <a href="{{ route('ceo.users.show', $user) }}" class="px-3 py-1 border rounded">Review</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td class="py-4" colspan="7">No users found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">{{ $users->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>


