<!-- System Admin Dashboard -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
    
    <!-- Total Users -->
    <div class="bg-white overflow-hidden shadow-lg rounded-lg">
        <div class="p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-8 w-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Total Users</dt>
                        <dd class="text-lg font-medium text-gray-900">{{ \App\Models\User::count() }}</dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="bg-gray-50 px-6 py-3">
            <div class="text-sm">
                <a href="#" class="font-medium text-cagsu-maroon hover:text-cagsu-orange">Manage users</a>
            </div>
        </div>
    </div>

    <!-- Active Departments -->
    <div class="bg-white overflow-hidden shadow-lg rounded-lg">
        <div class="p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-8 w-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Departments</dt>
                        <dd class="text-lg font-medium text-gray-900">{{ \App\Models\Department::where('is_active', true)->count() }}</dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="bg-gray-50 px-6 py-3">
            <div class="text-sm">
                <a href="#" class="font-medium text-cagsu-maroon hover:text-cagsu-orange">View departments</a>
            </div>
        </div>
    </div>

    <!-- System Health -->
    <div class="bg-white overflow-hidden shadow-lg rounded-lg">
        <div class="p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-8 w-8 text-cagsu-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">System Status</dt>
                        <dd class="text-lg font-medium text-green-600">Healthy</dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="bg-gray-50 px-6 py-3">
            <div class="text-sm">
                <a href="#" class="font-medium text-cagsu-maroon hover:text-cagsu-orange">System logs</a>
            </div>
        </div>
    </div>

    <!-- Database Size -->
    <div class="bg-white overflow-hidden shadow-lg rounded-lg">
        <div class="p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-8 w-8 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"></path>
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Database</dt>
                        <dd class="text-lg font-medium text-gray-900">13 Tables</dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="bg-gray-50 px-6 py-3">
            <div class="text-sm">
                <a href="#" class="font-medium text-cagsu-maroon hover:text-cagsu-orange">Database status</a>
            </div>
        </div>
    </div>

</div>

<!-- System Management -->
<div class="mt-6">
    <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg leading-6 font-medium text-gray-900">System Management</h3>
        </div>
        <div class="px-6 py-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                
                <!-- User Management -->
                <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50">
                    <div class="flex items-center">
                        <svg class="h-6 w-6 text-cagsu-maroon mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                        </svg>
                        <div>
                            <h4 class="font-medium text-gray-900">User Management</h4>
                            <p class="text-sm text-gray-500">Manage user accounts and roles</p>
                        </div>
                    </div>
                </div>

                <!-- Role Permissions -->
                <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50">
                    <div class="flex items-center">
                        <svg class="h-6 w-6 text-cagsu-maroon mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                        <div>
                            <h4 class="font-medium text-gray-900">Permissions</h4>
                            <p class="text-sm text-gray-500">Configure role permissions</p>
                        </div>
                    </div>
                </div>

                <!-- System Configuration -->
                <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50">
                    <div class="flex items-center">
                        <svg class="h-6 w-6 text-cagsu-maroon mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        <div>
                            <h4 class="font-medium text-gray-900">System Config</h4>
                            <p class="text-sm text-gray-500">Configure system settings</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>