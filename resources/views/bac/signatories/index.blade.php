@section('title', 'BAC Signatories Management')

<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-2xl text-gray-800 leading-tight">{{ __('BAC Signatories Management') }}</h2>
        </div>
    </x-slot>
    
    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
            <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                    {{ session('status') }}
                </div>
                @endif
                
                @if (session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <p class="text-gray-600 mb-0">Manage BAC signatories who can appear on resolution documents. These signatories will be available for selection when generating resolutions.</p>
                        <a href="{{ route('bac.signatories.create') }}" class="bg-cagsu-blue hover:bg-blue-800 text-white font-semibold py-2 px-4 rounded-lg shadow whitespace-nowrap">
                            Add New Signatory
                        </a>
                    </div>

                    @if($signatories->isEmpty())
                        <div class="text-center py-8">
                            <p class="text-gray-500 mb-4">No signatories added yet.</p>
                            <a href="{{ route('bac.signatories.create') }}" class="text-cagsu-blue hover:underline">Add your first signatory</a>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Position</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Full Name (w/ Titles)</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($signatories as $signatory)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900">{{ $signatory->display_name }}</div>
                                                @if($signatory->user)
                                                    <div class="text-sm text-gray-500">{{ $signatory->user->email }}</div>
                                                @else
                                                    <div class="text-sm text-gray-500 italic">Manual Entry</div>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="text-sm text-gray-900">{{ $signatory->position_name }}</span>
                                            </td>
                                            <td class="px-6 py-4">
                                                <span class="text-sm font-semibold text-gray-900">{{ $signatory->full_name }}</span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @if($signatory->is_active)
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Active</span>
                                                @else
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Inactive</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <a href="{{ route('bac.signatories.edit', $signatory) }}" class="text-cagsu-blue hover:text-blue-900 mr-3">Edit</a>
                                                <form action="{{ route('bac.signatories.destroy', $signatory) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to remove this signatory?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-900">Remove</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4">
                            {{ $signatories->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

