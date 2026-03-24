@section('title', 'Budget Office - Earmark Review')

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="font-semibold text-2xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Earmark Review: ') . $purchaseRequest->pr_number }}
            </h2>
            <div class="flex items-center gap-4">
                @if($purchaseRequest->earmark_id)
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 text-sm font-semibold rounded-full">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        {{ $purchaseRequest->earmark_id }}
                    </span>
                @endif
                <a href="{{ route('budget.purchase-requests.export-earmark', $purchaseRequest) }}"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Export Earmark
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- PR Summary Card --}}
            @include('budget.purchase_requests.partials.summary')

            {{-- CEO Comments --}}
            @if($ceoApproval && $ceoApproval->comments)
            <div class="bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 rounded-lg p-4">
                <div class="text-sm font-semibold text-green-800 dark:text-green-300 mb-1">CEO Comments</div>
                <div class="text-gray-900 dark:text-gray-100">{{ $ceoApproval->comments }}</div>
            </div>
            @endif

            {{-- Earmark Form --}}
            <form method="POST" action="{{ route('budget.purchase-requests.update', $purchaseRequest) }}" class="space-y-6">
                @csrf
                @method('PUT')

            {{-- Object of Expenditures --}}
            @php
                $initialObjectExpenditures = old('earmark_object_expenditures', $purchaseRequest->earmark_object_expenditures ?? []);
                if (! is_array($initialObjectExpenditures) || count($initialObjectExpenditures) === 0) {
                    $initialObjectExpenditures = [['code' => null, 'description' => null, 'amount' => null]];
                }
            @endphp
            <div
                x-data="{
                    rows: {{ json_encode(array_values($initialObjectExpenditures)) }},
                    addRow() {
                        this.rows.push({ code: null, description: null, amount: null });
                    },
                    removeRow(index) {
                        if (this.rows.length > 1) {
                            this.rows.splice(index, 1);
                        }
                    }
                }"
                class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden"
            >
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between gap-2">
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Object of Expenditures</h3>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            These rows map to A19–C19+ in the earmark template. Example: <span class="font-mono">(50213040-02). R &amp; M School Buildings</span>.
                        </p>
                    </div>
                    <button type="button"
                        @click="addRow()"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 text-white text-xs font-medium rounded-md hover:bg-emerald-700 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Add Row
                    </button>
                </div>
                <div class="p-6 space-y-3">
                    <template x-for="(row, index) in rows" :key="index">
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-start">
                            <div class="md:col-span-3">
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                    Code
                                    <span class="font-normal text-[10px] text-gray-500 dark:text-gray-400">(e.g. (50213040-02))</span>
                                </label>
                                <input type="text"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-cagsu-maroon focus:ring-cagsu-maroon text-xs"
                                    x-model="row.code"
                                    :name="`earmark_object_expenditures[${index}][code]`"
                                    placeholder="(50213040-02)">
                            </div>
                            <div class="md:col-span-6">
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                    Description
                                    <span class="font-normal text-[10px] text-gray-500 dark:text-gray-400">(e.g. R &amp; M School Buildings)</span>
                                </label>
                                <input type="text"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-cagsu-maroon focus:ring-cagsu-maroon text-xs"
                                    x-model="row.description"
                                    :name="`earmark_object_expenditures[${index}][description]`"
                                    placeholder="R &amp; M School Buildings">
                            </div>
                            <div class="md:col-span-3 flex items-end gap-2">
                                <div class="flex-1">
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">Amount (₱)</label>
                                    <input type="number" step="0.01" min="0"
                                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-cagsu-maroon focus:ring-cagsu-maroon text-xs"
                                        x-model="row.amount"
                                        :name="`earmark_object_expenditures[${index}][amount]`">
                                </div>
                                <button type="button"
                                    @click="removeRow(index)"
                                    class="inline-flex items-center justify-center mt-5 px-2.5 py-1.5 border border-gray-300 dark:border-gray-600 rounded-md text-xs text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-40"
                                    :disabled="rows.length === 1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 12H6"/></svg>
                                </button>
                            </div>
                        </div>
                    </template>
                    <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                        If no per-row amounts are set, the approved budget total will still be reflected in the template.
                    </p>
                </div>
            </div>

            {{-- PR Items (for reference only, not Object of Expenditures) --}}
            @include('budget.purchase_requests.partials.pr-items')

            {{-- Earmark Form --}}
            <div class="space-y-6">

                {{-- Section 1: Procurement Details --}}
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Procurement Details</h3>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="date_needed" value="Current Date" />
                                <x-text-input id="date_needed" name="date_needed" type="date" class="mt-1 block w-full bg-gray-100 dark:bg-gray-700"
                                    value="{{ now()->format('Y-m-d') }}" required readonly />
                                <x-input-error :messages="$errors->get('date_needed')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="earmark_date_to" value="Earmark Date To" />
                                <x-text-input id="earmark_date_to" name="earmark_date_to" type="date" class="mt-1 block w-full"
                                    :value="old('earmark_date_to', $purchaseRequest->earmark_date_to?->format('Y-m-d'))" />
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">End date for the earmark validity range (A7 in template).</p>
                                <x-input-error :messages="$errors->get('earmark_date_to')" class="mt-2" />
                            </div>

                            <div>
                                @php
                                    $initialFundClusterCode = old('fund_cluster_code', $purchaseRequest->fund_cluster_code);
                                    $initialFundDetails = old('fund_details', $purchaseRequest->fund_details);
                                @endphp

                                <div
                                    x-data="{
                                        fundClusterCode: @js($initialFundClusterCode),
                                        fundDetails: @js($initialFundDetails),
                                        fundClusters: [
                                            { code: '01', label: 'Regular Agency Fund' },
                                            { code: '05', label: 'Off-Budgetary Fund' },
                                            { code: '06', label: 'Income Generating Enterprise' },
                                            { code: '07', label: 'Trust Receipts' },
                                        ],
                                        fundDetailsOptions: {
                                            '01': [
                                                'General Fund - New General Appropriations - Specific Budget of National',
                                            ],
                                            '05': [],
                                            '06': [],
                                            '07': [],
                                        },
                                        get fundClusterLabel() {
                                            const match = this.fundClusters.find(c => c.code === this.fundClusterCode);
                                            return match ? match.label : '';
                                        },
                                        get availableDetails() {
                                            return this.fundClusterCode ? (this.fundDetailsOptions[this.fundClusterCode] || []) : [];
                                        },
                                        normalizeDetails(details) {
                                            const d = (details || '').toString().trim();
                                            return d.replace(/\\s*\\(\\s*\\d{2}\\s*\\)\\s*$/, '').trim();
                                        },
                                        get preview() {
                                            if (!this.fundClusterCode || !this.fundClusterLabel) {
                                                return '';
                                            }
                                            const details = this.normalizeDetails(this.fundDetails);
                                            if (!details) {
                                                return `${this.fundClusterLabel} (${this.fundClusterCode})`;
                                            }
                                            return `${this.fundClusterLabel} - ${details} (${this.fundClusterCode})`;
                                        },
                                        onClusterChanged() {
                                            if (!this.fundClusterCode) {
                                                this.fundDetails = null;
                                                return;
                                            }
                                            // Custom fund details are allowed even when no preset options exist.
                                            // Only clear details when the cluster is cleared.
                                        },
                                    }"
                                    x-init="onClusterChanged()"
                                    class="space-y-2"
                                >
                                    <x-input-label for="fund_cluster_code" value="Fund Cluster" />
                                    <select
                                        id="fund_cluster_code"
                                        name="fund_cluster_code"
                                        x-model="fundClusterCode"
                                        @change="onClusterChanged()"
                                        class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md"
                                    >
                                        <option value="">Select Fund Cluster</option>
                                        <template x-for="cluster in fundClusters" :key="cluster.code">
                                            <option :value="cluster.code" x-text="`${cluster.label} (${cluster.code})`"></option>
                                        </template>
                                    </select>
                                    <x-input-error :messages="$errors->get('fund_cluster_code')" class="mt-2" />

                                    <div class="pt-1">
                                        <x-input-label for="fund_details" value="Fund Details (Optional)" />
                                        <select
                                            id="fund_details_select"
                                            x-show="fundClusterCode && availableDetails.length > 0"
                                            x-cloak
                                            @change="fundDetails = $event.target.value"
                                            class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md"
                                        >
                                            <option value="">Select Fund Details</option>
                                            <template x-for="opt in availableDetails" :key="opt">
                                                <option :value="opt" x-text="opt"></option>
                                            </template>
                                        </select>

                                        <input
                                            id="fund_details"
                                            name="fund_details"
                                            type="text"
                                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm disabled:opacity-60"
                                            x-model="fundDetails"
                                            :disabled="!fundClusterCode"
                                            placeholder="Type custom fund details (optional)"
                                        />
                                        <x-input-error :messages="$errors->get('fund_details')" class="mt-2" />
                                    </div>

                                    <p class="text-xs text-gray-500 dark:text-gray-400" x-show="preview">
                                        Will be saved as: <span class="font-medium text-gray-800 dark:text-gray-100" x-text="preview"></span>
                                    </p>
                                </div>
                            </div>

                            <div>
                                <x-input-label for="procurement_type" value="Procurement Type" />
                                <select id="procurement_type" name="procurement_type" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md" required>
                                    <option value="">Select Type</option>
                                    <option value="supplies_materials" {{ old('procurement_type', $purchaseRequest->procurement_type) == 'supplies_materials' ? 'selected' : '' }}>Supplies / Materials</option>
                                    <option value="equipment" {{ old('procurement_type', $purchaseRequest->procurement_type) == 'equipment' ? 'selected' : '' }}>Equipment</option>
                                    <option value="infrastructure" {{ old('procurement_type', $purchaseRequest->procurement_type) == 'infrastructure' ? 'selected' : '' }}>Infrastructure</option>
                                    <option value="services" {{ old('procurement_type', $purchaseRequest->procurement_type) == 'services' ? 'selected' : '' }}>Services</option>
                                    <option value="consulting_services" {{ old('procurement_type', $purchaseRequest->procurement_type) == 'consulting_services' ? 'selected' : '' }}>Consulting Services</option>
                                </select>
                                <x-input-error :messages="$errors->get('procurement_type')" class="mt-2" />
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Section 2: Earmark Details (maps to template) --}}
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Earmark Details</h3>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">These fields map directly to the Earmark Template document.</p>
                    </div>
                    <div class="p-6 space-y-4">
                        <div>
                            <x-input-label for="pr_title" value="PR Title (Optional)" />
                            <x-text-input
                                id="pr_title"
                                name="pr_title"
                                type="text"
                                class="mt-1 block w-full"
                                :value="old('pr_title', $purchaseRequest->pr_title)"
                                placeholder="Optional title used in the earmark export"
                            />
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Maps to cell B14 in the earmark template.</p>
                            <x-input-error :messages="$errors->get('pr_title')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="legal_basis" value="Legal Basis" />
                            <x-text-input id="legal_basis" name="legal_basis" type="text" class="mt-1 block w-full"
                                :value="old('legal_basis', $purchaseRequest->legal_basis)"
                                placeholder="e.g. Section 86 of RA 9184" />
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Maps to cell B11 in the earmark template.</p>
                            <x-input-error :messages="$errors->get('legal_basis')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="earmark_programs_activities" value="Programs / Projects / Activities" />
                            <textarea id="earmark_programs_activities" name="earmark_programs_activities" rows="2"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md"
                                placeholder="Describe the programs, projects, or activities covered by this earmark">{{ old('earmark_programs_activities', $purchaseRequest->earmark_programs_activities) }}</textarea>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Maps to cell A16 in the earmark template.</p>
                            <x-input-error :messages="$errors->get('earmark_programs_activities')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="earmark_responsibility_center" value="Responsibility Center" />
                            <x-text-input id="earmark_responsibility_center" name="earmark_responsibility_center" type="text" class="mt-1 block w-full"
                                :value="old('earmark_responsibility_center', $purchaseRequest->earmark_responsibility_center)"
                                placeholder="e.g. Office of the University President" />
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Maps to cell A17 in the earmark template.</p>
                            <x-input-error :messages="$errors->get('earmark_responsibility_center')" class="mt-2" />
                        </div>
                    </div>
                </div>

                {{-- Section 3: Budget Approval --}}
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Budget Approval</h3>
                    </div>
                    <div class="p-6 space-y-4">
                        <div>
                            <x-input-label for="approved_budget_total" value="Approved Budget Total (₱)" />
                            <x-text-input id="approved_budget_total" name="approved_budget_total" type="number" step="0.01" class="mt-1 block w-full"
                                value="{{ old('approved_budget_total', number_format((float)$purchaseRequest->estimated_total, 2, '.', '')) }}" required />
                            <x-input-error :messages="$errors->get('approved_budget_total')" class="mt-2" />
                        </div>

                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <x-input-label for="remarks" value="Remarks *" class="mb-0" />
                                <button type="button" id="copy-purpose-btn-remarks" class="inline-flex items-center text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-200 transition-colors relative group text-xs gap-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                    Copy from purpose
                                </button>
                            </div>
                            <textarea id="remarks" name="remarks" rows="3"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md"
                                required placeholder="Required to forward to CEO for approval">{{ old('remarks') }}</textarea>
                            <x-input-error :messages="$errors->get('remarks')" class="mt-2" />
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Remarks are required to forward to CEO for approval. Maps to cell B13 in the earmark template.</p>
                        </div>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="flex items-center justify-between gap-3 flex-wrap">
                    <a href="{{ route('budget.purchase-requests.index') }}"
                        class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                        Cancel
                    </a>
                    <div class="flex items-center gap-3">
                        <button type="button" id="show-deferral-form-btn"
                            class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors">
                            Defer Request
                        </button>
                        <x-primary-button>Approve &amp; Forward to CEO</x-primary-button>
                    </div>
                </div>
            </div>

            {{-- Deferral Form (Hidden by default) --}}
            <div id="deferral-section" class="hidden">
                <form method="POST" action="{{ route('budget.purchase-requests.reject', $purchaseRequest) }}" id="deferral-form">
                    @csrf
                    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-red-200 dark:border-red-800">
                            <h3 class="text-base font-semibold text-red-700 dark:text-red-400">Defer Purchase Request</h3>
                        </div>
                        <div class="p-6 space-y-4">
                            <div>
                                <x-input-label for="rejection_reason" value="Deferral Reason *" />
                                <textarea id="rejection_reason" name="rejection_reason" rows="4"
                                    class="mt-1 block w-full border-red-300 dark:border-red-700 dark:bg-gray-800 dark:text-gray-300 rounded-md"
                                    required placeholder="Provide a detailed reason for deferral (minimum 10 characters)"></textarea>
                                <x-input-error :messages="$errors->get('rejection_reason')" class="mt-2" />
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Please provide a clear and detailed explanation for why this purchase request is being deferred.</p>
                            </div>

                            <div>
                                <x-input-label for="rejection_remarks" value="Additional Remarks" />
                                <textarea id="rejection_remarks" name="remarks" rows="2"
                                    class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md"
                                    placeholder="Optional additional remarks"></textarea>
                            </div>
                        </div>
                        <div class="px-6 py-4 bg-red-50 dark:bg-red-900/20 border-t border-red-200 dark:border-red-800 flex justify-end gap-3">
                            <button type="button" id="cancel-deferral-btn"
                                class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                                Cancel Deferral
                            </button>
                            <button type="submit"
                                class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors">
                                Confirm Deferral
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            {{-- Activity Timeline --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
                <div class="bg-gradient-to-r from-cagsu-maroon to-cagsu-orange px-6 py-4">
                    <h3 class="text-lg font-bold text-white">Activity Timeline</h3>
                </div>
                <div class="p-6">
                    <x-pr-timeline :activities="$purchaseRequest->activities" />
                </div>
            </div>

        </div>
    </div>

    {{-- Deferral Reason Modal --}}
    <x-rejection-reason-modal id="rejection-modal" />

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const showDeferralBtn = document.getElementById('show-deferral-form-btn');
            const cancelDeferralBtn = document.getElementById('cancel-deferral-btn');
            const deferralSection = document.getElementById('deferral-section');
            const deferralForm = document.getElementById('deferral-form');
            const deferralReasonField = document.getElementById('rejection_reason');
            const modal = document.getElementById('rejection-modal');
            const modalCloseBtn = modal ? modal.querySelector('[data-modal-close]') : null;
            const purposeText = document.getElementById('purpose-text');
            const remarksField = document.getElementById('remarks');
            const approvalForm = document.querySelector('form[action*="update"]');

            // Copy purpose to remarks (both buttons)
            function copyPurposeToRemarks() {
                if (purposeText && remarksField) {
                    remarksField.value = purposeText.textContent.trim();
                    remarksField.focus();
                }
            }

            const copyBtn1 = document.getElementById('copy-purpose-btn');
            const copyBtn2 = document.getElementById('copy-purpose-btn-remarks');

            if (copyBtn1) { copyBtn1.addEventListener('click', copyPurposeToRemarks); }
            if (copyBtn2) { copyBtn2.addEventListener('click', copyPurposeToRemarks); }

            // Validate remarks before submitting approval form
            if (approvalForm) {
                approvalForm.addEventListener('submit', function (e) {
                    const remarks = remarksField ? remarksField.value.trim() : '';
                    if (remarks.length === 0) {
                        e.preventDefault();
                        alert('Remarks are required. Please provide remarks before forwarding to CEO.');
                        if (remarksField) { remarksField.focus(); }
                        return false;
                    }
                });
            }

            // Show deferral form
            if (showDeferralBtn) {
                showDeferralBtn.addEventListener('click', function () {
                    deferralSection.classList.remove('hidden');
                    deferralSection.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    deferralReasonField.focus();
                });
            }

            // Cancel deferral
            if (cancelDeferralBtn) {
                cancelDeferralBtn.addEventListener('click', function () {
                    deferralSection.classList.add('hidden');
                    deferralReasonField.value = '';
                    const rejectionRemarks = document.getElementById('rejection_remarks');
                    if (rejectionRemarks) { rejectionRemarks.value = ''; }
                });
            }

            // Validate deferral reason before submit
            if (deferralForm) {
                deferralForm.addEventListener('submit', function (e) {
                    const reason = deferralReasonField.value.trim();
                    if (reason.length < 10) {
                        e.preventDefault();
                        if (modal) { modal.classList.remove('hidden'); }
                        deferralReasonField.focus();
                    }
                });
            }

            // Close modal
            if (modalCloseBtn && modal) {
                modalCloseBtn.addEventListener('click', function () { modal.classList.add('hidden'); });
            }

            if (modal) {
                modal.addEventListener('click', function (e) {
                    if (e.target === modal) { modal.classList.add('hidden'); }
                });
            }
        });
    </script>
</x-app-layout>
