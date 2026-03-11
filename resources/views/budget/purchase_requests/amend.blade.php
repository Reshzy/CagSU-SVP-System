@section('title', 'Amend Earmark - ' . $purchaseRequest->earmark_id)

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4 justify-between">
            <div>
                <h2 class="font-semibold text-2xl text-gray-800 dark:text-gray-200 leading-tight">
                    Amend Earmark
                </h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ $purchaseRequest->pr_number }} &mdash;
                    <span class="font-mono font-semibold text-green-700 dark:text-green-400">{{ $purchaseRequest->earmark_id }}</span>
                </p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('budget.purchase-requests.export-earmark', $purchaseRequest) }}"
                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Export Earmark
                </a>
                <a href="{{ route('budget.purchase-requests.index') }}"
                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-md hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Back
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('status'))
                <div class="p-4 rounded-lg bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 text-green-800 dark:text-green-300 text-sm font-medium">
                    {{ session('status') }}
                </div>
            @endif

            @if($errors->any())
                <div class="p-4 rounded-lg bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 text-red-800 dark:text-red-300 text-sm">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- PR Summary --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
                <div class="bg-gradient-to-r from-cagsu-maroon to-cagsu-orange px-6 py-4">
                    <h3 class="text-base font-bold text-white">Purchase Request Summary</h3>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Requester</div>
                        <div class="font-semibold text-gray-900 dark:text-gray-100">{{ $purchaseRequest->requester?->name ?? 'N/A' }}</div>
                    </div>
                    <div>
                        <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Department</div>
                        <div class="font-semibold text-gray-900 dark:text-gray-100">{{ $purchaseRequest->department?->name ?? 'N/A' }}</div>
                    </div>
                    <div>
                        <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Current Status</div>
                        <div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-300">
                                {{ ucwords(str_replace('_', ' ', $purchaseRequest->status)) }}
                            </span>
                        </div>
                    </div>
                    <div>
                        <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Purpose</div>
                        <div class="text-sm text-gray-700 dark:text-gray-300">{{ $purchaseRequest->purpose }}</div>
                    </div>
                    <div>
                        <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Approved Budget</div>
                        <div class="font-semibold text-gray-900 dark:text-gray-100">₱{{ number_format((float) $purchaseRequest->estimated_total, 2) }}</div>
                    </div>
                    <div>
                        <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Earmark No.</div>
                        <div class="font-mono font-semibold text-green-700 dark:text-green-400">{{ $purchaseRequest->earmark_id }}</div>
                    </div>
                </div>
            </div>

            {{-- Amendment Form --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center gap-3">
                    <div class="p-2 rounded-lg bg-amber-100 dark:bg-amber-900/40 text-amber-600 dark:text-amber-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Amend Earmark Fields</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Changes are logged with the time and author. Workflow status is not affected.</p>
                    </div>
                </div>
                @php
                    $initialObjectExpenditures = old('earmark_object_expenditures', $purchaseRequest->earmark_object_expenditures ?? []);
                    if (! is_array($initialObjectExpenditures) || count($initialObjectExpenditures) === 0) {
                        $initialObjectExpenditures = [['code' => null, 'description' => null, 'amount' => null]];
                    }
                @endphp
                <form method="POST" action="{{ route('budget.purchase-requests.amend-earmark', $purchaseRequest) }}" class="p-6 space-y-5">
                    @csrf
                    @method('PATCH')

                    {{-- Object of Expenditures --}}
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
                        class="space-y-3 mb-4"
                    >
                        <div class="flex items-center justify-between gap-2">
                            <div>
                                <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Object of Expenditures</h4>
                                <p class="mt-0.5 text-[11px] text-gray-500 dark:text-gray-400">
                                    Edit the rows that map to A19–C19+ in the earmark template. Example: <span class="font-mono">(50213040-02). R &amp; M School Buildings</span>.
                                </p>
                            </div>
                            <button type="button"
                                @click="addRow()"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 text-white text-xs font-medium rounded-md hover:bg-emerald-700 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                Add Row
                            </button>
                        </div>
                        <template x-for="(row, index) in rows" :key="index">
                            <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-start">
                                <div class="md:col-span-3">
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">Code</label>
                                    <input type="text"
                                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-cagsu-maroon focus:ring-cagsu-maroon text-xs"
                                        x-model="row.code"
                                        :name="`earmark_object_expenditures[${index}][code]`">
                                </div>
                                <div class="md:col-span-6">
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">Description</label>
                                    <input type="text"
                                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-cagsu-maroon focus:ring-cagsu-maroon text-xs"
                                        x-model="row.description"
                                        :name="`earmark_object_expenditures[${index}][description]`">
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
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        {{-- Fund / Funding Source --}}
                        <div>
                            <label for="funding_source" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Fund / Funding Source</label>
                            <input type="text" id="funding_source" name="funding_source"
                                value="{{ old('funding_source', $purchaseRequest->funding_source) }}"
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-cagsu-maroon focus:ring-cagsu-maroon sm:text-sm"
                                placeholder="e.g. General Fund">
                            @error('funding_source')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Legal Basis --}}
                        <div>
                            <label for="legal_basis" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Legal Basis</label>
                            <input type="text" id="legal_basis" name="legal_basis"
                                value="{{ old('legal_basis', $purchaseRequest->legal_basis) }}"
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-cagsu-maroon focus:ring-cagsu-maroon sm:text-sm"
                                placeholder="e.g. RA 9184">
                            @error('legal_basis')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Earmark Date To --}}
                        <div>
                            <label for="earmark_date_to" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Earmark Date To</label>
                            <input type="date" id="earmark_date_to" name="earmark_date_to"
                                value="{{ old('earmark_date_to', $purchaseRequest->earmark_date_to?->toDateString()) }}"
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-cagsu-maroon focus:ring-cagsu-maroon sm:text-sm">
                            @error('earmark_date_to')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Programs / Projects / Activities --}}
                    <div>
                        <label for="earmark_programs_activities" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Programs / Projects / Activities</label>
                        <textarea id="earmark_programs_activities" name="earmark_programs_activities" rows="2"
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-cagsu-maroon focus:ring-cagsu-maroon sm:text-sm"
                            placeholder="Programs/Projects/Activities covered by this earmark">{{ old('earmark_programs_activities', $purchaseRequest->earmark_programs_activities) }}</textarea>
                        @error('earmark_programs_activities')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Responsibility Center --}}
                    <div>
                        <label for="earmark_responsibility_center" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Responsibility Center</label>
                        <input type="text" id="earmark_responsibility_center" name="earmark_responsibility_center"
                            value="{{ old('earmark_responsibility_center', $purchaseRequest->earmark_responsibility_center) }}"
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-cagsu-maroon focus:ring-cagsu-maroon sm:text-sm"
                            placeholder="e.g. Office of the President">
                        @error('earmark_responsibility_center')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Remarks --}}
                    <div>
                        <label for="remarks" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Amendment Remarks / Notes</label>
                        <textarea id="remarks" name="remarks" rows="3"
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-cagsu-maroon focus:ring-cagsu-maroon sm:text-sm"
                            placeholder="Reason for amendment (e.g., price adjustment after canvassing)">{{ old('remarks', $purchaseRequest->current_step_notes) }}</textarea>
                        @error('remarks')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <a href="{{ route('budget.purchase-requests.index') }}"
                            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                            Cancel
                        </a>
                        <button type="submit"
                            class="inline-flex items-center gap-2 px-5 py-2 bg-amber-500 text-white text-sm font-semibold rounded-md hover:bg-amber-600 transition-colors shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Save Amendment
                        </button>
                    </div>
                </form>
            </div>

            {{-- Amendment History --}}
            <div
                x-data="{
                    loading: false,
                    async go(url) {
                        if (!url) { return; }
                        this.loading = true;
                        try {
                            const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                            const html = await res.text();
                            this.$refs.container.innerHTML = html;
                            window.history.replaceState({}, '', url);
                        } finally {
                            this.loading = false;
                        }
                    },
                    intercept(e) {
                        const a = e.target.closest('a');
                        if (!a) { return; }
                        if (!a.closest('nav')) { return; }
                        const href = a.getAttribute('href');
                        if (!href) { return; }
                        e.preventDefault();
                        this.go(href);
                    }
                }"
                @click="intercept($event)"
                class="relative"
            >
                <div x-show="loading" class="absolute inset-0 bg-white/60 dark:bg-gray-800/60 backdrop-blur-[1px]"></div>
                <div x-ref="container">
                    @include('budget.purchase_requests.partials.amendment-history', ['amendmentHistory' => $amendmentHistory])
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
