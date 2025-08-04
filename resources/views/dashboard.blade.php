@section('title', 'Dashboard')

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-gray-800 leading-tight flex items-center">
            <svg class="w-8 h-8 mr-3 text-cagsu-maroon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
            </svg>
            {{ __('CagSU SVP Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <!-- Welcome Section -->
            <div class="bg-gradient-to-r from-cagsu-yellow to-cagsu-orange text-white overflow-hidden shadow-lg rounded-lg mb-8">
                <div class="p-8">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-2xl font-bold mb-2">Welcome back, {{ Auth::user()->name }}!</h3>
                            <p class="text-lg opacity-90">{{ Auth::user()->getPrimarySVPRole() }} | {{ Auth::user()->department ? Auth::user()->department->name : 'No Department' }}</p>
                            <p class="text-sm opacity-75 mt-2">Streamlining CagSU's procurement process - from 49 days to 25 days</p>
                        </div>
                        <div class="text-right">
                            <div class="text-4xl font-bold">{{ now()->format('d') }}</div>
                            <div class="text-lg">{{ now()->format('M Y') }}</div>
                            <div class="text-sm opacity-75">{{ now()->format('l') }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Role-Based Dashboard Content -->
            @if(auth()->user()->hasRole('System Admin'))
                @include('dashboard.admin')
            @elseif(auth()->user()->hasRole('Supply Officer'))
                @include('dashboard.supply-officer')
            @elseif(auth()->user()->hasRole('Executive Officer'))
                @include('dashboard.executive')
            @elseif(auth()->user()->hasRole(['BAC Chair', 'BAC Members', 'BAC Secretariat']))
                @include('dashboard.bac')
            @elseif(auth()->user()->hasRole('Budget Office'))
                @include('dashboard.budget')
            @elseif(auth()->user()->hasRole('Accounting Office'))
                @include('dashboard.accounting')
            @else
                @include('dashboard.end-user')
            @endif

            <!-- Quick Actions -->
            <div class="mt-8">
                <h4 class="text-lg font-semibold text-gray-800 mb-4">Quick Actions</h4>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    
                    @can('create-purchase-request')
                        <a href="#" class="bg-white hover:bg-cagsu-yellow hover:text-white p-4 rounded-lg shadow-md transition-all duration-200 flex flex-col items-center text-center group">
                            <svg class="w-8 h-8 text-cagsu-maroon group-hover:text-white mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            <span class="font-medium">New PR</span>
                            <span class="text-xs text-gray-500 group-hover:text-white">Create Request</span>
                        </a>
                    @endcan

                    @can('view-workflow-status')
                        <a href="#" class="bg-white hover:bg-cagsu-orange hover:text-white p-4 rounded-lg shadow-md transition-all duration-200 flex flex-col items-center text-center group">
                            <svg class="w-8 h-8 text-cagsu-maroon group-hover:text-white mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                            <span class="font-medium">Track Status</span>
                            <span class="text-xs text-gray-500 group-hover:text-white">View Progress</span>
                        </a>
                    @endcan

                    @can('view-reports')
                        <a href="#" class="bg-white hover:bg-cagsu-maroon hover:text-white p-4 rounded-lg shadow-md transition-all duration-200 flex flex-col items-center text-center group">
                            <svg class="w-8 h-8 text-cagsu-maroon group-hover:text-white mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                            <span class="font-medium">Reports</span>
                            <span class="text-xs text-gray-500 group-hover:text-white">View Analytics</span>
                        </a>
                    @endcan

                    @can('manage-users')
                        <a href="#" class="bg-white hover:bg-gov-dark hover:text-white p-4 rounded-lg shadow-md transition-all duration-200 flex flex-col items-center text-center group">
                            <svg class="w-8 h-8 text-cagsu-maroon group-hover:text-white mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            <span class="font-medium">Settings</span>
                            <span class="text-xs text-gray-500 group-hover:text-white">System Config</span>
                        </a>
                    @endcan

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
