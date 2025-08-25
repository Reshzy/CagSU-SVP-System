@section('title', 'New Purchase Request')

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-gray-800 leading-tight">{{ __('Create Purchase Request') }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form action="{{ route('purchase-requests.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                        @csrf

                        <div>
                            <x-input-label for="purpose" value="Purpose" />
                            <x-text-input id="purpose" name="purpose" type="text" class="mt-1 block w-full" required />
                            <x-input-error :messages="$errors->get('purpose')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="justification" value="Justification" />
                            <textarea id="justification" name="justification" class="mt-1 block w-full border-gray-300 rounded-md" rows="3"></textarea>
                            <x-input-error :messages="$errors->get('justification')" class="mt-2" />
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <x-input-label for="date_needed" value="Date Needed" />
                                <x-text-input id="date_needed" name="date_needed" type="date" class="mt-1 block w-full" required />
                                <x-input-error :messages="$errors->get('date_needed')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="priority" value="Priority" />
                                <select id="priority" name="priority" class="mt-1 block w-full border-gray-300 rounded-md" required>
                                    <option value="low">Low</option>
                                    <option value="medium" selected>Medium</option>
                                    <option value="high">High</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                                <x-input-error :messages="$errors->get('priority')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="estimated_total" value="Estimated Total" />
                                <x-text-input id="estimated_total" name="estimated_total" type="number" step="0.01" class="mt-1 block w-full" required />
                                <x-input-error :messages="$errors->get('estimated_total')" class="mt-2" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <x-input-label for="funding_source" value="Funding Source" />
                                <x-text-input id="funding_source" name="funding_source" type="text" class="mt-1 block w-full" />
                                <x-input-error :messages="$errors->get('funding_source')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="budget_code" value="Budget Code" />
                                <x-text-input id="budget_code" name="budget_code" type="text" class="mt-1 block w-full" />
                                <x-input-error :messages="$errors->get('budget_code')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="procurement_type" value="Procurement Type" />
                                <select id="procurement_type" name="procurement_type" class="mt-1 block w-full border-gray-300 rounded-md" required>
                                    <option value="supplies_materials">Supplies/Materials</option>
                                    <option value="equipment">Equipment</option>
                                    <option value="infrastructure">Infrastructure</option>
                                    <option value="services">Services</option>
                                    <option value="consulting_services">Consulting Services</option>
                                </select>
                                <x-input-error :messages="$errors->get('procurement_type')" class="mt-2" />
                            </div>
                        </div>

                        <div>
                            <x-input-label for="procurement_method" value="Procurement Method" />
                            <select id="procurement_method" name="procurement_method" class="mt-1 block w-full border-gray-300 rounded-md">
                                <option value="">Select Method</option>
                                <option value="small_value_procurement">Small Value Procurement</option>
                                <option value="public_bidding">Public Bidding</option>
                                <option value="direct_contracting">Direct Contracting</option>
                                <option value="negotiated_procurement">Negotiated Procurement</option>
                            </select>
                            <x-input-error :messages="$errors->get('procurement_method')" class="mt-2" />
                        </div>

                        <div class="border-t pt-4">
                            <div class="text-lg font-semibold mb-2">Item</div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <x-input-label for="item_name" value="Item Name" />
                                    <x-text-input id="item_name" name="item_name" type="text" class="mt-1 block w-full" required />
                                    <x-input-error :messages="$errors->get('item_name')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="unit_of_measure" value="Unit of Measure" />
                                    <x-text-input id="unit_of_measure" name="unit_of_measure" type="text" class="mt-1 block w-full" required />
                                    <x-input-error :messages="$errors->get('unit_of_measure')" class="mt-2" />
                                </div>
                                <div class="md:col-span-2">
                                    <x-input-label for="detailed_specifications" value="Detailed Specifications" />
                                    <textarea id="detailed_specifications" name="detailed_specifications" class="mt-1 block w-full border-gray-300 rounded-md" rows="4" required></textarea>
                                    <x-input-error :messages="$errors->get('detailed_specifications')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="quantity_requested" value="Quantity" />
                                    <x-text-input id="quantity_requested" name="quantity_requested" type="number" min="1" class="mt-1 block w-full" required />
                                    <x-input-error :messages="$errors->get('quantity_requested')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="estimated_unit_cost" value="Estimated Unit Cost" />
                                    <x-text-input id="estimated_unit_cost" name="estimated_unit_cost" type="number" step="0.01" class="mt-1 block w-full" required />
                                    <x-input-error :messages="$errors->get('estimated_unit_cost')" class="mt-2" />
                                </div>
                            </div>
                        </div>

                        <div>
                            <x-input-label for="attachments" value="Attachments" />
                            <input id="attachments" name="attachments[]" type="file" multiple class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('attachments.*')" class="mt-2" />
                        </div>

                        <div class="flex justify-end space-x-3">
                            <a href="{{ route('purchase-requests.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md">Cancel</a>
                            <x-primary-button>Submit PR</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>


