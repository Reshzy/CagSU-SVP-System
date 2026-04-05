<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white leading-tight">Department Management</h2>
                <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">Manage departments and review incoming department requests.</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <livewire:ceo.department-management />
        </div>
    </div>
</x-app-layout>
