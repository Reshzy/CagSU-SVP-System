@section('title', 'PO Signatories Management')

<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-2xl text-gray-800 leading-tight">{{ __('PO Signatories Management') }}</h2>
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

            <!-- Configuration Status Card -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Required Signatory Positions</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @php
                            $ceoConfigured = $signatories->where('position', 'ceo')->where('is_active', true)->isNotEmpty();
                            $caConfigured = $signatories->where('position', 'chief_accountant')->where('is_active', true)->isNotEmpty();
                        @endphp
                        
                        <div class="border rounded-lg p-4 {{ $ceoConfigured ? 'border-green-300 bg-green-50' : 'border-red-300 bg-red-50' }}">
                            <div class="flex items-center justify-between mb-2">
                                <h4 class="text-sm font-semibold {{ $ceoConfigured ? 'text-green-900' : 'text-red-900' }}">
                                    CEO
                                </h4>
                                @if($ceoConfigured)
                                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                @else
                                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                @endif
                            </div>
                            <div class="text-xs {{ $ceoConfigured ? 'text-green-700' : 'text-red-700' }}">
                                @if($ceoConfigured)
                                    <span class="font-medium">Configured</span>
                                @else
                                    <span class="font-medium">Not configured</span>
                                @endif
                            </div>
                        </div>

                        <div class="border rounded-lg p-4 {{ $caConfigured ? 'border-green-300 bg-green-50' : 'border-red-300 bg-red-50' }}">
                            <div class="flex items-center justify-between mb-2">
                                <h4 class="text-sm font-semibold {{ $caConfigured ? 'text-green-900' : 'text-red-900' }}">
                                    Chief Accountant
                                </h4>
                                @if($caConfigured)
                                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                @else
                                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                @endif
                            </div>
                            <div class="text-xs {{ $caConfigured ? 'text-green-700' : 'text-red-700' }}">
                                @if($caConfigured)
                                    <span class="font-medium">Configured</span>
                                @else
                                    <span class="font-medium">Not configured</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    @if(!$ceoConfigured || !$caConfigured)
                        <div class="mt-4 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                            <div class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-yellow-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                </svg>
                                <div class="flex-1">
                                    <h4 class="text-sm font-semibold text-yellow-900 mb-1">Configuration Incomplete</h4>
                                    <p class="text-sm text-yellow-800">Please configure all required signatory positions to enable PO document generation.</p>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="mt-4 bg-green-50 border border-green-200 rounded-lg p-4">
                            <div class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-green-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <div class="flex-1">
                                    <h4 class="text-sm font-semibold text-green-900 mb-1">All Positions Configured</h4>
                                    <p class="text-sm text-green-800">All required signatory positions are configured. Purchase Orders will automatically use these signatories.</p>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <p class="text-gray-600 mb-0">Manage PO signatories who will automatically appear on Purchase Order documents.</p>
                        <a href="{{ route('supply.po-signatories.create') }}" class="bg-cagsu-blue hover:bg-blue-800 text-white font-semibold py-2 px-4 rounded-lg shadow whitespace-nowrap">
                            Add New Signatory
                        </a>
                    </div>

                    @if ($signatories->isEmpty())
                        <div class="text-center py-8 text-gray-500">
                            <p>No signatories configured yet.</p>
                            <a href="{{ route('supply.po-signatories.create') }}" class="text-blue-600 hover:text-blue-800 underline mt-2 inline-block">
                                Add your first signatory
                            </a>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Position</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Full Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($signatories as $signatory)
                                        <tr class="{{ $signatory->is_active ? '' : 'opacity-50' }}">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                {{ $signatory->position_name }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                                {{ $signatory->display_name }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                                {{ $signatory->full_name }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @if ($signatory->is_active)
                                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Active</span>
                                                @else
                                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Inactive</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium gap-2">
                                                <a href="{{ route('supply.po-signatories.edit', $signatory) }}" class="text-blue-600 hover:text-blue-900">Edit</a>
                                                <form action="{{ route('supply.po-signatories.destroy', $signatory) }}" method="POST" class="inline-block ml-2" onsubmit="return confirm('Are you sure you want to delete this signatory?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
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
