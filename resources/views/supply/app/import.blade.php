<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Import Annual Procurement Plan (APP)') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form action="{{ route('supply.app.process') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="mb-4">
                            <label for="fiscal_year" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Fiscal Year
                            </label>
                            <input
                                type="number"
                                name="fiscal_year"
                                id="fiscal_year"
                                value="{{ old('fiscal_year', date('Y')) }}"
                                min="2020"
                                max="2100"
                                required
                                class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600"
                            />
                            @error('fiscal_year')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="csv_file" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                CSV File
                            </label>
                            <input
                                type="file"
                                name="csv_file"
                                id="csv_file"
                                accept=".csv,.txt"
                                required
                                class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600"
                            />
                            @error('csv_file')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                Upload the APP CSV file. The file should contain item codes, names, units, and prices.
                            </p>
                        </div>

                        <div class="bg-blue-50 dark:bg-blue-900 border-l-4 border-blue-400 p-4 mb-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-blue-700 dark:text-blue-200">
                                        <strong>Note:</strong> Importing a CSV will create or update APP items for the selected fiscal year.
                                        Existing items with the same item code and fiscal year will be updated.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="flex gap-2">
                            <button
                                type="submit"
                                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"
                            >
                                Import APP
                            </button>
                            <a
                                href="{{ route('supply.app.index') }}"
                                class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"
                            >
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

