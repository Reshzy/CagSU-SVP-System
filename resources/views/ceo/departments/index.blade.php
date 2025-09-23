<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">CEO • Departments</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if(session('status'))
                        <div class="mb-4 text-green-700">{{ session('status') }}</div>
                    @endif

                    <div class="mb-4 flex justify-between items-center">
                        <h3 class="text-lg font-medium">Departments</h3>
                        <a href="{{ route('ceo.departments.create') }}" class="px-4 py-2 border rounded">New Department</a>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-left text-sm">
                            <thead>
                                <tr class="border-b">
                                    <th class="py-2">Name</th>
                                    <th class="py-2">Code</th>
                                    <th class="py-2">Head</th>
                                    <th class="py-2">Email</th>
                                    <th class="py-2">Phone</th>
                                    <th class="py-2">Active</th>
                                    <th class="py-2"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($departments as $dept)
                                    <tr class="border-b">
                                        <td class="py-2">{{ $dept->name }}</td>
                                        <td class="py-2">{{ $dept->code }}</td>
                                        <td class="py-2">{{ $dept->head_name ?? '—' }}</td>
                                        <td class="py-2">{{ $dept->contact_email ?? '—' }}</td>
                                        <td class="py-2">{{ $dept->contact_phone ?? '—' }}</td>
                                        <td class="py-2">{{ $dept->is_active ? 'Yes' : 'No' }}</td>
                                        <td class="py-2 text-right">
                                            <a href="{{ route('ceo.departments.edit', $dept) }}" class="px-3 py-1 border rounded">Edit</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td class="py-4" colspan="7">No departments found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">{{ $departments->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>


