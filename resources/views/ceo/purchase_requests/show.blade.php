@section('title', 'CEO - PR Details')

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-gray-800 leading-tight">{{ __('PR Details: ') . $purchaseRequest->pr_number }}</h2>
    </x-slot>

    <!-- Rejection Reason Modal Component -->
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
                            <div class="text-sm text-yellow-800">Budget Office Notes</div>
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
                            <x-input-label for="rejection_reason" value="Rejection Reason (if rejecting)" />
                            <textarea id="rejection_reason" name="rejection_reason" rows="2" class="mt-1 block w-full border-gray-300 rounded-md"></textarea>
                        </div>
                        <div class="flex justify-end space-x-3">
                            <a href="{{ route('ceo.purchase-requests.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md">Back</a>
                            <button id="reject-button" name="decision" value="reject" disabled class="px-4 py-2 bg-red-600 text-white rounded-md disabled:opacity-50 disabled:cursor-not-allowed">Reject</button>
                            <button name="decision" value="approve" class="px-4 py-2 bg-green-600 text-white rounded-md">Approve</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const rejectionReasonTextarea = document.getElementById('rejection_reason');
            const rejectButton = document.getElementById('reject-button');
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
                
                // Focus on the rejection reason textarea after modal closes
                setTimeout(() => {
                    rejectionReasonTextarea.focus();
                    rejectionReasonTextarea.scrollIntoView({ behavior: 'smooth', block: 'center' });
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

            // Check if user came from reject action (URL parameter)
            const urlParams = new URLSearchParams(window.location.search);
            const action = urlParams.get('action');
            
            if (action === 'reject') {
                // Show custom modal
                showModal();
                
                // Remove the action parameter from URL (clean up)
                const newUrl = window.location.pathname;
                window.history.replaceState({}, document.title, newUrl);
            }

            // Function to check if rejection reason has non-whitespace content
            function validateRejectionReason() {
                const trimmedValue = rejectionReasonTextarea.value.trim();
                
                if (trimmedValue.length > 0) {
                    rejectButton.disabled = false;
                } else {
                    rejectButton.disabled = true;
                }
            }

            // Listen for input events on the rejection reason textarea
            rejectionReasonTextarea.addEventListener('input', validateRejectionReason);
            rejectionReasonTextarea.addEventListener('keyup', validateRejectionReason);
            rejectionReasonTextarea.addEventListener('change', validateRejectionReason);

            // Add validation on form submit to prevent rejection without reason
            const form = rejectionReasonTextarea.closest('form');
            form.addEventListener('submit', function(e) {
                const submittedButton = document.activeElement;
                
                // If reject button was clicked, ensure there's a rejection reason
                if (submittedButton && submittedButton.value === 'reject') {
                    const trimmedReason = rejectionReasonTextarea.value.trim();
                    
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
