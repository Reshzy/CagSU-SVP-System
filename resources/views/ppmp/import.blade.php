<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Import PPMP from PS DBMS CSV') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">

                    @if (session('import_output'))
                        <div class="bg-gray-100 dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded p-4 mb-6">
                            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Import Output</h4>
                            <pre class="text-xs text-gray-600 dark:text-gray-400 whitespace-pre-wrap">{{ session('import_output') }}</pre>
                        </div>
                    @endif

                    <form action="{{ route('ppmp.import.process') }}" method="POST" enctype="multipart/form-data">
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
                                PS DBMS CSV File
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
                        </div>

                        <div class="bg-blue-50 dark:bg-blue-900/30 border-l-4 border-blue-400 p-4 mb-6">
                            <div class="flex gap-3">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="text-sm text-blue-700 dark:text-blue-300 space-y-1">
                                    <p><strong>How this import works:</strong></p>
                                    <ul class="list-disc list-inside space-y-1">
                                        <li>Upload the standard <strong>PS DBMS CSV file / PPMP CSV file</strong> for the selected fiscal year.</li>
                                        <li>The importer reads <strong>Q1–Q4 quantities</strong> from the monthly breakdown columns.</li>
                                        <li>Only items with <strong>at least one non-zero quarterly quantity</strong> are imported.</li>
                                        <li>Items <strong>not in the current PS DBMS reference catalog</strong> for this fiscal year will be skipped (import PS DBMS first).</li>
                                        <li>This is a <strong>merge</strong> — existing PPMP items are updated; items not in the CSV are left unchanged.</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="flex gap-2">
                            <button
                                type="submit"
                                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"
                            >
                                Import PPMP
                            </button>
                            <a
                                href="{{ route('ppmp.index') }}"
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
