@section('title', 'CEO - PR Details')

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-gray-800 leading-tight">{{ __('PR Details: ') . $purchaseRequest->pr_number }}</h2>
    </x-slot>

    <!-- Deferral Reason Modal Component -->
    <x-rejection-reason-modal />

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <div class="text-sm text-gray-600">Requester</div>
                            <div class="font-medium">{{ $purchaseRequest->requester?->name }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-600">Department</div>
                            <div class="font-medium">{{ $purchaseRequest->department?->name }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-600">Purpose</div>
                            <div class="font-medium">{{ $purchaseRequest->purpose }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-600">Estimated Total</div>
                            <div class="font-medium">₱{{ number_format((float)$purchaseRequest->estimated_total, 2) }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-600">Date Created</div>
                            <div class="font-medium">{{ $purchaseRequest->created_at->format('F d, Y h:i A') }}</div>
                        </div>
                        <div class="md:col-span-2">
                            <div class="text-sm text-gray-600">Justification</div>
                            <div class="font-medium">{{ $purchaseRequest->justification ?: 'N/A' }}</div>
                        </div>
                    </div>

                    @if($purchaseRequest->current_step_notes)
                        <div class="bg-yellow-50 border border-yellow-200 rounded p-3">
                            <div class="text-sm text-yellow-800">Budget Office Remarks</div>
                            <div class="text-sm text-yellow-900">{{ $purchaseRequest->current_step_notes }}</div>
                        </div>
                    @endif

                    <div>
                        <div class="text-sm text-gray-600">Items</div>
                        <ul class="list-disc ml-5">
                            @foreach($purchaseRequest->items as $it)
                                <li>{{ $it->item_name }} ({{ $it->quantity_requested }} x {{ number_format((float)$it->estimated_unit_cost,2) }})</li>
                            @endforeach
                        </ul>
                    </div>

                    <div>
                        <div class="text-sm text-gray-600">Attachments</div>
                        @forelse($purchaseRequest->documents as $doc)
                            <div class="text-sm"><a class="text-cagsu-maroon" href="{{ route('files.show', $doc) }}" target="_blank">{{ $doc->file_name }}</a></div>
                        @empty
                            <div class="text-sm text-gray-500">No attachments</div>
                        @endforelse
                    </div>

                    <form action="{{ route('ceo.purchase-requests.update', $purchaseRequest) }}" method="POST" class="space-y-4">
                        @csrf
                        @method('PUT')
                        <div>
                            <x-input-label for="comments" value="Comments" />
                            <textarea id="comments" name="comments" rows="3" class="mt-1 block w-full border-gray-300 rounded-md"></textarea>
                        </div>
                        <div>
                            <x-input-label for="rejection_reason" value="Deferral Reason (if deferring)" />
                            <textarea id="rejection_reason" name="rejection_reason" rows="2" class="mt-1 block w-full border-gray-300 rounded-md"></textarea>
                        </div>
                        <div class="flex justify-end space-x-3">
                            <a href="{{ route('ceo.purchase-requests.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md">Back</a>
                            <button id="defer-button" name="decision" value="reject" disabled class="px-4 py-2 bg-red-600 text-white rounded-md disabled:opacity-50 disabled:cursor-not-allowed">Defer</button>
                            <button name="decision" value="approve" class="px-4 py-2 bg-green-600 text-white rounded-md">Approve</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Activity Timeline -->
            <div class="mt-6">
                <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                    <div class="bg-gradient-to-r from-cagsu-maroon to-cagsu-orange px-6 py-4">
                        <h3 class="text-lg font-bold text-white">Activity Timeline</h3>
                    </div>
                    <div class="p-6">
                        <x-pr-timeline :activities="$purchaseRequest->activities" />
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const deferralReasonTextarea = document.getElementById('rejection_reason');
            const deferButton = document.getElementById('defer-button');
            const modal = document.getElementById('rejection-modal');
            const closeModalBtn = document.querySelector('[data-modal-close="rejection-modal"]');

            // Function to show modal
            function showModal() {
                modal.classList.remove('hidden');
                document.body.style.overflow = 'hidden'; // Prevent background scrolling
            }

            // Function to hide modal
            function hideModal() {
                modal.classList.add('hidden');
                document.body.style.overflow = ''; // Restore scrolling
                
                // Focus on the deferral reason textarea after modal closes
                setTimeout(() => {
                    deferralReasonTextarea.focus();
                    deferralReasonTextarea.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }, 100);
            }

            // Close modal button click event
            if (closeModalBtn) {
                closeModalBtn.addEventListener('click', hideModal);
            }

            // Close modal when clicking outside the modal content
            if (modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target === modal || e.target.classList.contains('bg-opacity-75')) {
                        hideModal();
                    }
                });
            }

            // Close modal with Escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && modal && !modal.classList.contains('hidden')) {
                    hideModal();
                }
            });

            // Check if user came from defer action (URL parameter)
            const urlParams = new URLSearchParams(window.location.search);
            const action = urlParams.get('action');
            
            if (action === 'reject') {
                // Show custom modal
                showModal();
                
                // Remove the action parameter from URL (clean up)
                const newUrl = window.location.pathname;
                window.history.replaceState({}, document.title, newUrl);
            }

            // Function to check if deferral reason has non-whitespace content
            function validateDeferralReason() {
                const trimmedValue = deferralReasonTextarea.value.trim();
                
                if (trimmedValue.length > 0) {
                    deferButton.disabled = false;
                } else {
                    deferButton.disabled = true;
                }
            }

            // Listen for input events on the deferral reason textarea
            deferralReasonTextarea.addEventListener('input', validateDeferralReason);
            deferralReasonTextarea.addEventListener('keyup', validateDeferralReason);
            deferralReasonTextarea.addEventListener('change', validateDeferralReason);

            // Add validation on form submit to prevent deferral without reason
            const form = deferralReasonTextarea.closest('form');
            form.addEventListener('submit', function(e) {
                const submittedButton = document.activeElement;
                
                // If defer button was clicked, ensure there's a deferral reason
                if (submittedButton && submittedButton.value === 'reject') {
                    const trimmedReason = deferralReasonTextarea.value.trim();
                    
                    if (trimmedReason.length === 0) {
                        e.preventDefault();
                        showModal(); // Show modal instead of alert
                        return false;
                    }
                }
            });
        });
    </script>
</x-app-layout>
