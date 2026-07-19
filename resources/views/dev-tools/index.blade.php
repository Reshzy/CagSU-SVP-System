<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Dev Tools</h2>
        <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
            System Admin testing overrides for PRs, budgets, PPMPs, and workflow stages
        </p>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <livewire:dev-tools.hub />
        </div>
    </div>
</x-app-layout>
