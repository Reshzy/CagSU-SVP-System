<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AOQ Generator</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Century+Gothic:wght@400;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 px-4 py-10 font-['Century_Gothic','Segoe_UI',sans-serif] text-slate-900">
    <main class="mx-auto flex w-full max-w-5xl flex-col gap-10 rounded-3xl bg-white/95 px-6 py-10 shadow-2xl ring-1 ring-slate-200 backdrop-blur md:px-12 lg:px-16">
        <header class="space-y-4">
            <div class="space-y-2">
                <p class="text-sm uppercase tracking-[0.2em] text-blue-500">Abstract of Quotation</p>
                <h1 class="text-3xl font-semibold tracking-tight text-slate-900">AOQ Generator</h1>
            </div>
            <p class="text-base leading-relaxed text-slate-600">
                Generate the latest AOQ layout (legal 8.5×13, landscape, Century Gothic) straight into a downloadable
                Word document powered by PHPWord.
            </p>
            <ul class="list-disc space-y-1 pl-5 text-sm text-slate-600">
                <li>Pre-filled sample data mirroring the official template.</li>
                <li>Customize everything by editing the fields below.</li>
                <li>Outputs a `.docx` file you can forward or print immediately.</li>
            </ul>
        </header>

        @php
            $supplierDefaults = [
                ['name' => 'AW COMMERCIAL', 'location' => 'SANCHEZ MIRA, CAGAYAN'],
                ['name' => 'MIGRANTS SCHOOL AND OFFICE SUPPLIES', 'location' => 'SANCHEZ MIRA, CAGAYAN'],
                ['name' => "LIENMAVEL SHOPPER'S MART", 'location' => 'SANCHEZ MIRA, CAGAYAN'],
                ['name' => '', 'location' => ''],
            ];

            $itemDefaults = [
                [
                    'qty' => 1,
                    'unit' => 'BOX',
                    'article' => 'EXAMINATION GLOVES (SURGICAL GLOVES), 100PC/BOX, LARGE',
                    'prices' => [
                        ['u' => 'NONE', 't' => null],
                        ['u' => 650, 't' => 650],
                        ['u' => 'NONE', 't' => null],
                        ['u' => '', 't' => ''],
                    ],
                ],
                [
                    'qty' => 1,
                    'unit' => 'PACK',
                    'article' => 'CLEAR POLYETHYLENE (6x12), 100PCS/PACK, 0.002 THICKNESS',
                    'prices' => [
                        ['u' => 90.5, 't' => 90.5],
                        ['u' => 'NONE', 't' => null],
                        ['u' => 0, 't' => 0],
                        ['u' => '', 't' => ''],
                    ],
                ],
            ];

            $signatureDefaults = [
                ['position' => 'BAC Chairman', 'name' => 'Christopher R. Garingan'],
                ['position' => 'BAC Vice Chairman', 'name' => 'Atty. Jan Leandro P. Verzon'],
                ['position' => 'BAC Member', 'name' => 'Melvin S. Atayan'],
                ['position' => 'BAC Member', 'name' => 'Valentin M. Apostol'],
                ['position' => 'BAC Member', 'name' => 'Chris Ian T. Rodriguez'],
            ];

            $approverDefaults = [
                'head_bac' => 'Christie Melflor L. Aguirre',
                'ceo' => 'Rodel Francisco T. Alegado, Ph.D.',
            ];

            $suppliers = old('suppliers', request()->input('suppliers', $supplierDefaults));
            $suppliers = array_values($suppliers);
            $supplierCount = max(4, count($suppliers), count($supplierDefaults));
            for ($i = 0; $i < $supplierCount; $i++) {
                $defaults = $supplierDefaults[$i] ?? ['name' => 'SUPPLIER ' . ($i + 1), 'location' => ''];
                $suppliers[$i] = array_merge($defaults, $suppliers[$i] ?? []);
            }

            $items = old('items', request()->input('items', []));
            if (count($items) === 0) {
                $items = $itemDefaults;
            }
            $items = array_values($items);
            foreach ($items as $index => $item) {
                $defaults = $itemDefaults[$index] ?? ['qty' => 1, 'unit' => '', 'article' => '', 'prices' => []];
                $items[$index]['qty'] = $item['qty'] ?? $defaults['qty'];
                $items[$index]['unit'] = $item['unit'] ?? $defaults['unit'];
                $items[$index]['article'] = $item['article'] ?? $defaults['article'];
                $items[$index]['prices'] = $item['prices'] ?? [];
                for ($s = 0; $s < $supplierCount; $s++) {
                    $priceDefaults = $defaults['prices'][$s] ?? ['u' => '', 't' => ''];
                    $items[$index]['prices'][$s] = [
                        'u' => $items[$index]['prices'][$s]['u'] ?? $priceDefaults['u'],
                        't' => $items[$index]['prices'][$s]['t'] ?? $priceDefaults['t'],
                    ];
                }
            }

            $itemWinnerInputRaw = request()->input('item_winners', []);
            $itemWinnerSelections = [];
            if (is_array($itemWinnerInputRaw)) {
                foreach ($itemWinnerInputRaw as $itemIndex => $winnerValues) {
                    if (!is_array($winnerValues)) {
                        continue;
                    }

                    $normalized = [];
                    foreach ($winnerValues as $value) {
                        if ($value === '' || $value === null) {
                            continue;
                        }

                        if (is_numeric($value)) {
                            $supplierIndex = (int) $value;
                            if ($supplierIndex >= 0 && $supplierIndex < $supplierCount) {
                                $normalized[] = $supplierIndex;
                            }
                        }
                    }

                    $normalized = array_values(array_unique($normalized));
                    if (count($normalized) > 0) {
                        $itemWinnerSelections[$itemIndex] = $normalized;
                    }
                }
            }

            $signatoriesInput = old('signatories', request()->input('signatories', []));
            $signatories = [];
            foreach ($signatureDefaults as $index => $defaults) {
                $input = $signatoriesInput[$index] ?? [];
                $signatories[] = [
                    'position' => $defaults['position'],
                    'name' => $input['name'] ?? $defaults['name'],
                ];
            }

            $approverInput = old('approvers', request()->input('approvers', []));
            $approvers = [
                'head_bac' => $approverInput['head_bac'] ?? $approverDefaults['head_bac'],
                'ceo' => $approverInput['ceo'] ?? $approverDefaults['ceo'],
            ];
        @endphp

        <form method="GET" action="{{ route('quotation.sample') }}" class="space-y-10" id="aoq-form" data-supplier-count="{{ $supplierCount }}">
            <div class="space-y-6 rounded-3xl border border-slate-200 bg-slate-50/70 p-6 shadow-inner">
                <div class="space-y-2">
                    <label for="purpose" class="text-sm font-semibold text-slate-700">Purpose</label>
                    <textarea
                        id="purpose"
                        name="purpose"
                        rows="4"
                        class="w-full rounded-2xl border border-slate-200 bg-white/70 px-4 py-3 text-sm leading-relaxed shadow-inner transition focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-200"
                    >{{ old('purpose', 'FOR THE IMPLEMENTATION AND CONDUT OF TRAINING UNDER EXTENSION PROGRAM SARANAY TITLED, “HOLISTIC COMMUNITY EMPOWERMENT THROUGH CAPACITY-BUILDING ON HEALTH, SANITATION, SAFETY, LIVELIHOOD, AND FINANCIAL LITERACY”') }}</textarea>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    @foreach ([
                        ['label' => 'AOQ No.', 'name' => 'aoq_no', 'value' => old('aoq_no', '2025.0249')],
                        ['label' => 'P.R. No.', 'name' => 'pr_no', 'value' => old('pr_no', '11.2025.0652')],
                        ['label' => 'RFQ No.', 'name' => 'rfq_no', 'value' => old('rfq_no', '2025.0785-0787')],
                        ['label' => 'Date', 'name' => 'date', 'value' => old('date', '11.14.2025')],
                    ] as $field)
                        <div class="space-y-2">
                            <label for="{{ $field['name'] }}" class="text-sm font-semibold text-slate-700">{{ $field['label'] }}</label>
                            <input
                                id="{{ $field['name'] }}"
                                name="{{ $field['name'] }}"
                                type="text"
                                value="{{ $field['value'] }}"
                                class="w-full rounded-2xl border border-slate-200 bg-white/70 px-4 py-2.5 text-sm shadow-inner transition placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-200"
                            >
                        </div>
                    @endforeach
                </div>
            </div>

            <section class="space-y-6">
                <div>
                    <h2 class="text-xl font-semibold text-slate-900">Suppliers</h2>
                    <p class="text-sm text-slate-500">Provide at least four suppliers (the last can stay blank for quick bids).</p>
                </div>
                <div class="grid gap-6 md:grid-cols-2">
                    @foreach ($suppliers as $index => $supplier)
                        <div class="space-y-4 rounded-3xl border border-slate-200 bg-white/70 p-5 shadow-sm">
                            <div class="space-y-2">
                                <label for="supplier-name-{{ $index }}" class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Supplier {{ $loop->iteration }} Name
                                </label>
                                <input
                                    id="supplier-name-{{ $index }}"
                                    name="suppliers[{{ $index }}][name]"
                                    type="text"
                                    value="{{ $supplier['name'] }}"
                                    class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm shadow-inner focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                >
                            </div>
                            <div class="space-y-2">
                                <label for="supplier-location-{{ $index }}" class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Supplier {{ $loop->iteration }} Location
                                </label>
                                <input
                                    id="supplier-location-{{ $index }}"
                                    name="suppliers[{{ $index }}][location]"
                                    type="text"
                                    value="{{ $supplier['location'] }}"
                                    class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm shadow-inner focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                                >
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="space-y-4">
                <div>
                    <h2 class="text-xl font-semibold text-slate-900">Approval Signatories</h2>
                    <p class="text-sm text-slate-500">Edit the names that appear under the BAC Secretariat and Campus Executive Officer signature lines.</p>
                </div>
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="space-y-2">
                        <label for="approver-head-bac" class="text-xs font-semibold uppercase tracking-wide text-slate-500">Head - BAC Secretariat</label>
                        <input
                            id="approver-head-bac"
                            name="approvers[head_bac]"
                            type="text"
                            value="{{ $approvers['head_bac'] }}"
                            class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm shadow-inner focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                        >
                    </div>
                    <div class="space-y-2">
                        <label for="approver-ceo" class="text-xs font-semibold uppercase tracking-wide text-slate-500">Campus Executive Officer</label>
                        <input
                            id="approver-ceo"
                            name="approvers[ceo]"
                            type="text"
                            value="{{ $approvers['ceo'] }}"
                            class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm shadow-inner focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                        >
                    </div>
                </div>
            </section>

            <section class="space-y-4">
                <div>
                    <h2 class="text-xl font-semibold text-slate-900">Signatories</h2>
                    <p class="text-sm text-slate-500">Update the names that will appear beneath each signature line in the AOQ document.</p>
                </div>
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
                    @foreach ($signatories as $index => $signatory)
                        <div class="space-y-2 rounded-3xl border border-slate-200 bg-white/80 p-5 shadow-sm">
                            <label for="signatory-{{ $index }}" class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                {{ $signatory['position'] }}
                            </label>
                            <input
                                id="signatory-{{ $index }}"
                                name="signatories[{{ $index }}][name]"
                                type="text"
                                value="{{ $signatory['name'] }}"
                                class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm shadow-inner focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                            >
                            <input type="hidden" name="signatories[{{ $index }}][position]" value="{{ $signatory['position'] }}">
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="space-y-4">
                <div>
                    <h2 class="text-xl font-semibold text-slate-900">Line Items & Pricing</h2>
                    <p class="text-sm text-slate-500">Default items mirror the AOQ template—add more rows as required.</p>
                </div>
                <p class="text-xs uppercase tracking-wide text-slate-400">Enter unit prices for each supplier. Total prices are automatically calculated (quantity × unit price).</p>
                <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs leading-relaxed text-amber-800">
                    When two bids tie, the system auto-selects and highlights all lowest totals in yellow. BAC members can tap the <span class="font-semibold">Winner</span> chip under each supplier to keep only their preferred awardee before downloading the AOQ.
                </div>
                <div class="overflow-x-auto rounded-3xl border border-slate-200 bg-white shadow-sm">
                    <table class="min-w-[760px] w-full border-collapse text-sm text-slate-700" id="items-table">
                        <thead>
                            <tr>
                                <th rowspan="2" class="border border-slate-200 bg-slate-50 px-3 py-2 text-center text-xs font-semibold uppercase tracking-wide">No.</th>
                                <th rowspan="2" class="border border-slate-200 bg-slate-50 px-4 py-2 text-center text-xs font-semibold uppercase tracking-wide">Qty</th>
                                <th rowspan="2" class="border border-slate-200 bg-slate-50 px-7 py-2 text-center text-xs font-semibold uppercase tracking-wide">Unit</th>
                                <th rowspan="2" class="border border-slate-200 bg-slate-50 px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide">Article / Description</th>
                                @foreach ($suppliers as $supplier)
                                    <th colspan="2" class="border border-slate-200 bg-blue-50 px-3 py-2 text-center text-xs font-semibold uppercase tracking-wide text-blue-800">
                                        {{ $supplier['name'] !== '' ? $supplier['name'] : 'Supplier ' . $loop->iteration }}
                                    </th>
                                @endforeach
                            </tr>
                            <tr>
                                @foreach ($suppliers as $supplier)
                                    <th class="border border-slate-200 bg-slate-50 px-3 py-2 text-center text-[11px] font-semibold uppercase tracking-wide text-slate-500">U.Price</th>
                                    <th class="border border-slate-200 bg-slate-50 px-3 py-2 text-center text-[11px] font-semibold uppercase tracking-wide text-slate-500">T.Price</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody id="items-body">
                            @foreach ($items as $index => $item)
                                @php
                                    $manualWinners = $itemWinnerSelections[$index] ?? [];
                                @endphp
                                <tr class="item-row" data-item-index="{{ $index }}" data-user-override="{{ count($manualWinners) > 0 ? '1' : '0' }}">
                                    <td class="border border-slate-200 px-3 py-2 text-center font-semibold">{{ $loop->iteration }}</td>
                                    <td class="border border-slate-200 px-3 py-2">
                                        <input
                                            type="number"
                                            name="items[{{ $index }}][qty]"
                                            min="1"
                                            value="{{ $item['qty'] }}"
                                            class="qty-input w-full rounded-xl border border-slate-200 bg-white px-2 py-1 text-center text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-300"
                                        >
                                    </td>
                                    <td class="border border-slate-200 px-3 py-2">
                                        <input
                                            type="text"
                                            name="items[{{ $index }}][unit]"
                                            value="{{ $item['unit'] }}"
                                            class="w-full rounded-xl border border-slate-200 bg-white px-2 py-1 text-center text-sm uppercase focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-300"
                                        >
                                    </td>
                                    <td class="border border-slate-200 px-3 py-2">
                                        <textarea
                                            name="items[{{ $index }}][article]"
                                            rows="2"
                                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-300"
                                        >{{ $item['article'] }}</textarea>
                                    </td>
                                    @foreach ($suppliers as $supplierIndex => $supplier)
                                        @php
                                            $isPreselected = in_array($supplierIndex, $manualWinners, true);
                                        @endphp
                                        <td
                                            class="border border-slate-200 px-3 py-2"
                                            data-cell-type="unit"
                                            data-supplier-index="{{ $supplierIndex }}"
                                        >
                                            <div class="space-y-2">
                                                <input
                                                    type="text"
                                                    name="items[{{ $index }}][prices][{{ $supplierIndex }}][u]"
                                                    value="{{ $item['prices'][$supplierIndex]['u'] }}"
                                                    data-supplier-index="{{ $supplierIndex }}"
                                                    data-item-index="{{ $index }}"
                                                    class="unit-price-input w-full rounded-xl border border-slate-200 bg-white px-2 py-1 text-center text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-300"
                                                >
                                                <label class="winner-toggle-label flex items-center justify-center">
                                                    <input
                                                        type="checkbox"
                                                        name="item_winners[{{ $index }}][]"
                                                        value="{{ $supplierIndex }}"
                                                        class="winner-checkbox sr-only"
                                                        data-supplier-index="{{ $supplierIndex }}"
                                                        data-item-index="{{ $index }}"
                                                        data-from-request="{{ $isPreselected ? '1' : '0' }}"
                                                        {{ $isPreselected ? 'checked' : '' }}
                                                    >
                                                    <span
                                                        class="winner-chip inline-flex items-center justify-center rounded-full border border-slate-200 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-slate-500 transition"
                                                        data-winner-toggle
                                                        data-supplier-index="{{ $supplierIndex }}"
                                                        data-item-index="{{ $index }}"
                                                    >
                                                        Winner
                                                    </span>
                                                </label>
                                            </div>
                                        </td>
                                        <td
                                            class="border border-slate-200 px-3 py-2"
                                            data-cell-type="total"
                                            data-supplier-index="{{ $supplierIndex }}"
                                        >
                                            <input
                                                type="text"
                                                name="items[{{ $index }}][prices][{{ $supplierIndex }}][t]"
                                                value="{{ $item['prices'][$supplierIndex]['t'] }}"
                                                data-supplier-index="{{ $supplierIndex }}"
                                                data-item-index="{{ $index }}"
                                                class="total-price-input w-full rounded-xl border border-slate-200 bg-white px-2 py-1 text-center text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-300"
                                                readonly
                                            >
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="flex justify-end">
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-2xl border border-dashed border-slate-300 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:border-blue-400 hover:text-blue-600"
                        id="add-item"
                    >
                        + Add another item
                    </button>
                </div>
            </section>

            <div class="flex flex-wrap gap-4">
                <button
                    type="submit"
                    class="inline-flex flex-1 items-center justify-center gap-2 rounded-2xl bg-blue-600 px-6 py-3 text-base font-semibold text-white shadow-lg shadow-blue-500/30 transition hover:bg-blue-500 md:flex-none cursor-pointer"
                >
                    Download AOQ Document
                </button>
                <a
                    class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 px-6 py-3 text-base font-semibold text-slate-700 transition hover:border-slate-300 hover:text-slate-900"
                    href="https://github.com/PHPOffice/PHPWord"
                    target="_blank"
                    rel="noreferrer"
                >
                    PHPWord Docs
                </a>
            </div>
        </form>

        <footer class="text-center text-xs text-slate-500">
            Need more fields or dynamic data? Extend <span class="font-semibold">QuotationController@downloadSample</span>.
        </footer>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const addButton = document.getElementById('add-item');
            const itemsBody = document.getElementById('items-body');
            const form = document.getElementById('aoq-form');
            const supplierCount = Number(form?.dataset.supplierCount || 0);
            const cellHighlightClasses = ['bg-amber-50', 'ring-1', 'ring-amber-200', 'ring-offset-0'];
            const chipActiveClasses = ['bg-amber-50', 'border-amber-300', 'text-amber-700'];

            if (!addButton || !itemsBody || !supplierCount) {
                return;
            }

            function calculateTotalPrice(row, supplierIndex) {
                const qtyInput = row.querySelector(`input[name*="[qty]"]`);
                const unitPriceInput = row.querySelector(`input[name*="[prices][${supplierIndex}][u]"]`);
                const totalPriceInput = row.querySelector(`input[name*="[prices][${supplierIndex}][t]"]`);

                if (!qtyInput || !unitPriceInput || !totalPriceInput) {
                    return;
                }

                const qty = parseFloat(qtyInput.value) || 0;
                const unitPrice = parseFloat(unitPriceInput.value) || 0;

                if (qty > 0 && unitPrice > 0) {
                    const total = qty * unitPrice;
                    totalPriceInput.value = total.toFixed(2);
                } else if (unitPriceInput.value.trim() === '' || unitPriceInput.value.trim().toUpperCase() === 'NONE') {
                    totalPriceInput.value = '';
                }
            }

            function calculateAllTotalsForRow(row) {
                for (let i = 0; i < supplierCount; i++) {
                    calculateTotalPrice(row, i);
                }
            }

            function determineRowWinners(row) {
                const totals = [];
                let lowest = null;

                for (let i = 0; i < supplierCount; i++) {
                    const totalInput = row.querySelector(`input[name*="[prices][${i}][t]"]`);
                    if (!totalInput) {
                        totals[i] = null;
                        continue;
                    }

                    const value = parseFloat(totalInput.value);
                    if (Number.isFinite(value) && value > 0) {
                        totals[i] = value;
                        if (lowest === null || value < lowest) {
                            lowest = value;
                        }
                    } else {
                        totals[i] = null;
                    }
                }

                if (lowest === null) {
                    return [];
                }

                return totals
                    .map((value, index) => (value !== null && Math.abs(value - lowest) < 0.005 ? index : null))
                    .filter((value) => value !== null);
            }

            function applyWinnerVisuals(row) {
                const checkboxes = row.querySelectorAll('.winner-checkbox');
                checkboxes.forEach((checkbox) => {
                    const supplierIndex = checkbox.dataset.supplierIndex;
                    const isWinner = checkbox.checked;
                    const unitCell = row.querySelector(`td[data-cell-type="unit"][data-supplier-index="${supplierIndex}"]`);
                    const totalCell = row.querySelector(`td[data-cell-type="total"][data-supplier-index="${supplierIndex}"]`);
                    const chip = row.querySelector(`[data-winner-toggle][data-supplier-index="${supplierIndex}"]`);

                    [unitCell, totalCell].forEach((cell) => {
                        if (!cell) {
                            return;
                        }
                        cellHighlightClasses.forEach((cls) => cell.classList.toggle(cls, isWinner));
                    });

                    if (chip) {
                        chipActiveClasses.forEach((cls) => chip.classList.toggle(cls, isWinner));
                    }
                });
            }

            function refreshRowWinnerState(row) {
                if (row.dataset.userOverride === '1') {
                    applyWinnerVisuals(row);
                    return;
                }

                const winners = determineRowWinners(row);
                const checkboxes = row.querySelectorAll('.winner-checkbox');

                if (winners.length === 0) {
                    checkboxes.forEach((checkbox) => {
                        checkbox.checked = false;
                    });
                    applyWinnerVisuals(row);
                    return;
                }

                checkboxes.forEach((checkbox) => {
                    const supplierIndex = Number(checkbox.dataset.supplierIndex);
                    checkbox.checked = winners.includes(supplierIndex);
                });

                applyWinnerVisuals(row);
            }

            function attachRowEventListeners(row) {
                const qtyInput = row.querySelector(`input[name*="[qty]"]`);
                if (qtyInput) {
                    qtyInput.addEventListener('input', () => {
                        calculateAllTotalsForRow(row);
                        refreshRowWinnerState(row);
                    });
                }

                for (let i = 0; i < supplierCount; i++) {
                    const unitPriceInput = row.querySelector(`input[name*="[prices][${i}][u]"]`);
                    if (unitPriceInput) {
                        unitPriceInput.addEventListener('input', () => {
                            calculateTotalPrice(row, i);
                            refreshRowWinnerState(row);
                        });
                    }
                }
            }

            function attachWinnerToggleListeners(row) {
                const checkboxes = row.querySelectorAll('.winner-checkbox');
                if (!row.dataset.userOverride) {
                    row.dataset.userOverride = '0';
                }

                checkboxes.forEach((checkbox) => {
                    checkbox.addEventListener('change', () => {
                        const parentRow = checkbox.closest('.item-row');
                        if (!parentRow) {
                            return;
                        }

                        const checkedBoxes = Array.from(parentRow.querySelectorAll('.winner-checkbox')).filter((input) => input.checked);
                        if (checkedBoxes.length === 0) {
                            checkbox.checked = true;
                            window.alert('Please keep at least one winning supplier for this item.');
                            return;
                        }

                        parentRow.dataset.userOverride = '1';
                        applyWinnerVisuals(parentRow);
                    });
                });
            }

            function initializeRow(row) {
                attachRowEventListeners(row);
                attachWinnerToggleListeners(row);

                if (row.dataset.userOverride === '1') {
                    applyWinnerVisuals(row);
                } else {
                    refreshRowWinnerState(row);
                }
            }

            itemsBody.querySelectorAll('.item-row').forEach((row) => {
                initializeRow(row);
            });

            addButton.addEventListener('click', (event) => {
                event.preventDefault();
                const nextIndex = itemsBody.querySelectorAll('.item-row').length;
                const randomQty = Math.floor(Math.random() * 10) + 1;
                const row = document.createElement('tr');
                row.className = 'item-row';
                row.dataset.itemIndex = String(nextIndex);
                row.dataset.userOverride = '0';
                row.innerHTML = buildRowTemplate(nextIndex, supplierCount, randomQty);
                itemsBody.appendChild(row);
                initializeRow(row);
                row.scrollIntoView({ behavior: 'smooth', block: 'center' });
            });

            function buildRowTemplate(index, supplierCount, defaultQty = 1) {
                let supplierCells = '';
                for (let i = 0; i < supplierCount; i++) {
                    supplierCells += `
                        <td class="border border-slate-200 px-3 py-2" data-cell-type="unit" data-supplier-index="${i}">
                            <div class="space-y-2">
                                <input type="text" name="items[${index}][prices][${i}][u]" value="" data-supplier-index="${i}" data-item-index="${index}" class="unit-price-input w-full rounded-xl border border-slate-200 bg-white px-2 py-1 text-center text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-300">
                                <label class="winner-toggle-label flex items-center justify-center">
                                    <input type="checkbox" name="item_winners[${index}][]" value="${i}" class="winner-checkbox sr-only" data-supplier-index="${i}" data-item-index="${index}">
                                    <span class="winner-chip inline-flex items-center justify-center rounded-full border border-slate-200 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-slate-500 transition" data-winner-toggle data-supplier-index="${i}" data-item-index="${index}">
                                        Winner
                                    </span>
                                </label>
                            </div>
                        </td>
                        <td class="border border-slate-200 px-3 py-2" data-cell-type="total" data-supplier-index="${i}">
                            <input type="text" name="items[${index}][prices][${i}][t]" value="" data-supplier-index="${i}" data-item-index="${index}" class="total-price-input w-full rounded-xl border border-slate-200 bg-white px-2 py-1 text-center text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-300" readonly>
                        </td>
                    `;
                }

                return `
                    <td class="border border-slate-200 px-3 py-2 text-center font-semibold">${index + 1}</td>
                    <td class="border border-slate-200 px-3 py-2">
                        <input type="number" name="items[${index}][qty]" min="1" value="${defaultQty}" class="qty-input w-full rounded-xl border border-slate-200 bg-white px-2 py-1 text-center text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-300">
                    </td>
                    <td class="border border-slate-200 px-3 py-2">
                        <input type="text" name="items[${index}][unit]" value="PIECE" class="w-full rounded-xl border border-slate-200 bg-white px-2 py-1 text-center text-sm uppercase focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-300">
                    </td>
                    <td class="border border-slate-200 px-3 py-2">
                        <textarea name="items[${index}][article]" rows="2" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-300">SAMPLE</textarea>
                    </td>
                    ${supplierCells}
                `;
            }
        });
    </script>
</body>
</html>

