<!-- Generate/Regenerate Resolution Modal -->
<div id="regenerateModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-10 mx-auto p-5 border w-11/12 max-w-4xl shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-900">
                @if($resolution && $purchaseRequest->resolution_number)
                    Regenerate Resolution
                @else
                    Generate Resolution
                @endif
            </h3>
            <button onclick="document.getElementById('regenerateModal').classList.add('hidden')" 
                    class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <form action="{{ route('bac.quotations.resolution.regenerate', $purchaseRequest) }}" method="POST">
            @csrf
            
            <div class="mb-4">
                @if($resolution && $purchaseRequest->resolution_number)
                    <p class="text-sm text-gray-600">Update the signatory information and regenerate the BAC resolution document. Leave fields unchanged if you want to keep existing signatories.</p>
                @else
                    <div class="bg-blue-50 border border-blue-200 rounded-md p-3 mb-3">
                        <p class="text-sm text-blue-800">Configure the signatories for the BAC resolution document. You can select from pre-configured signatories or enter names manually.</p>
                    </div>
                    <p class="text-sm text-gray-600">The system will use default signatories from your BAC Signatories configuration if you leave these fields empty.</p>
                @endif
            </div>

            <!-- Signatory Selection Form -->
            @include('bac.partials.signatory_form', [
                'signatories' => $purchaseRequest->resolutionSignatories ?? null,
                'bacSignatories' => $bacSignatories ?? []
            ])

            <div class="flex justify-end space-x-3 mt-6">
                <button type="button" 
                        onclick="document.getElementById('regenerateModal').classList.add('hidden')"
                        class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 transition-colors">
                    Cancel
                </button>
                <button type="submit" 
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                    @if($resolution && $purchaseRequest->resolution_number)
                        Regenerate Resolution
                    @else
                        Generate Resolution
                    @endif
                </button>
            </div>
        </form>
    </div>
</div>

