<?php

namespace App\Livewire\DevTools;

use App\Models\Department;
use App\Models\DepartmentBudget;
use App\Models\Ppmp;
use App\Models\PpmpItem;
use App\Models\PurchaseRequest;
use App\Models\User;
use App\Services\DevTools\DevToolsPurchaseRequestService;
use App\Services\PpmpBudgetValidator;
use App\Services\PpmpQuarterlyTracker;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class Hub extends Component
{
    use WithFileUploads;
    use WithPagination;

    #[Url]
    public string $tab = 'overview';

    public int $fiscalYear;

    public ?int $departmentId = null;

    public int $createStep = 1;

    public ?int $requesterId = null;

    public string $purpose = '';

    public string $justification = '';

    public ?string $dateNeeded = null;

    public string $landingStatus = 'supply_office_review';

    public bool $notifyOfficers = false;

    public string $itemMode = 'ppmp';

    /**
     * Defined lots for the create-PR wizard (same multi-lot model as the end-user form).
     *
     * @var list<array{id: string, name: string, member_keys: list<string>}>
     */
    public array $lots = [];

    public bool $showLotForm = false;

    public ?string $editingLotId = null;

    public string $lotFormName = '';

    /** @var list<string> */
    public array $lotFormMemberKeys = [];

    /** @var list<int> */
    public array $selectedPpmpItemIds = [];

    /** @var array<int, array{quantity: int|string, unit_cost: float|string}> */
    public array $ppmpItemOverrides = [];

    /** @var list<array{key: string, item_name: string, unit_of_measure: string, quantity_requested: int|string, estimated_unit_cost: float|string, detailed_specifications: string}> */
    public array $manualItems = [];

    public ?string $createdPrNumber = null;

    public ?int $createdPrId = null;

    public string $budgetSearch = '';

    public ?int $editingBudgetDepartmentId = null;

    public string $editingAllocatedBudget = '';

    public string $editingBudgetNotes = '';

    public bool $confirmClearReserved = false;

    public $csvFile = null;

    public ?string $importOutput = null;

    public string $workflowSearch = '';

    public string $workflowStatusFilter = '';

    public ?int $selectedPrId = null;

    public string $workflowTargetStatus = 'supply_office_review';

    /**
     * @var list<string>
     */
    private const TABS = ['overview', 'create-pr', 'budgets', 'ppmp', 'workflow'];

    public function mount(): void
    {
        $this->fiscalYear = (int) date('Y');

        if (! in_array($this->tab, self::TABS, true)) {
            $this->tab = 'overview';
        }

        $firstDepartment = Department::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->value('id');

        $this->departmentId = $firstDepartment ? (int) $firstDepartment : null;

        $this->manualItems = [
            $this->emptyManualItem(),
        ];
    }

    public function updatedTab(string $value): void
    {
        if (! in_array($value, self::TABS, true)) {
            $this->tab = 'overview';
        }

        $this->resetPage();
    }

    public function updatedDepartmentId(): void
    {
        $this->requesterId = null;
        $this->selectedPpmpItemIds = [];
        $this->ppmpItemOverrides = [];
        $this->resetLots();
        $this->createStep = 1;
        $this->selectedPrId = null;
        $this->resetPage();
    }

    public function updatedFiscalYear(): void
    {
        $this->selectedPpmpItemIds = [];
        $this->ppmpItemOverrides = [];
        $this->resetLots();
        $this->resetPage();
    }

    public function updatedItemMode(): void
    {
        $this->resetLots();
    }

    public function setTab(string $tab): void
    {
        if (in_array($tab, self::TABS, true)) {
            $this->tab = $tab;
            $this->resetPage();
        }
    }

    public function nextCreateStep(): void
    {
        if ($this->createStep === 1) {
            $this->validate([
                'departmentId' => ['required', 'exists:departments,id'],
                'requesterId' => ['required', 'exists:users,id'],
            ], [], [
                'departmentId' => 'department',
                'requesterId' => 'requester',
            ]);
        }

        if ($this->createStep === 2) {
            $this->validate([
                'purpose' => ['required', 'string', 'max:1000'],
                'justification' => ['required', 'string', 'max:2000'],
                'dateNeeded' => ['nullable', 'date'],
                'landingStatus' => ['required', 'in:'.implode(',', DevToolsPurchaseRequestService::STATUSES)],
            ]);
        }

        if ($this->createStep < 4) {
            $this->createStep++;
        }
    }

    public function previousCreateStep(): void
    {
        if ($this->createStep > 1) {
            $this->createStep--;
        }
    }

    public function togglePpmpItem(int $ppmpItemId): void
    {
        if (in_array($ppmpItemId, $this->selectedPpmpItemIds, true)) {
            $this->selectedPpmpItemIds = array_values(array_filter(
                $this->selectedPpmpItemIds,
                fn (int $id): bool => $id !== $ppmpItemId
            ));
            unset($this->ppmpItemOverrides[$ppmpItemId]);
            $this->removeMemberKeyFromLots($this->ppmpMemberKey($ppmpItemId));

            return;
        }

        $ppmpItem = PpmpItem::with('appItem')->find($ppmpItemId);

        if (! $ppmpItem) {
            return;
        }

        $quarter = app(PpmpQuarterlyTracker::class)->getQuarterFromDate();
        $remaining = max(1, $ppmpItem->getRemainingQuantity($quarter));

        $this->selectedPpmpItemIds[] = $ppmpItemId;
        $this->ppmpItemOverrides[$ppmpItemId] = [
            'quantity' => min($remaining, max(1, $ppmpItem->getQuarterlyQuantity($quarter) ?: 1)),
            'unit_cost' => (float) $ppmpItem->estimated_unit_cost,
        ];
    }

    public function addManualItem(): void
    {
        $this->manualItems[] = $this->emptyManualItem();
    }

    public function removeManualItem(int $index): void
    {
        $key = $this->manualItems[$index]['key'] ?? null;

        unset($this->manualItems[$index]);
        $this->manualItems = array_values($this->manualItems);

        if (is_string($key) && $key !== '') {
            $this->removeMemberKeyFromLots($key);
        }

        if ($this->manualItems === []) {
            $this->manualItems[] = $this->emptyManualItem();
        }
    }

    public function openLotForm(?string $lotId = null): void
    {
        if ($lotId !== null) {
            $lot = collect($this->lots)->firstWhere('id', $lotId);

            if (! $lot) {
                return;
            }

            $this->editingLotId = $lotId;
            $this->lotFormName = $lot['name'];
            $this->lotFormMemberKeys = $lot['member_keys'];
        } else {
            $this->editingLotId = null;
            $this->lotFormName = '';
            $this->lotFormMemberKeys = [];
        }

        $this->showLotForm = true;
        $this->resetErrorBag(['lotFormName', 'lotFormMemberKeys']);
    }

    public function closeLotForm(): void
    {
        $this->showLotForm = false;
        $this->editingLotId = null;
        $this->lotFormName = '';
        $this->lotFormMemberKeys = [];
        $this->resetErrorBag(['lotFormName', 'lotFormMemberKeys']);
    }

    public function toggleLotFormMember(string $memberKey): void
    {
        if (in_array($memberKey, $this->lotFormMemberKeys, true)) {
            $this->lotFormMemberKeys = array_values(array_filter(
                $this->lotFormMemberKeys,
                fn (string $key): bool => $key !== $memberKey
            ));

            return;
        }

        $this->lotFormMemberKeys[] = $memberKey;
    }

    public function saveLot(): void
    {
        $this->validate([
            'lotFormName' => ['required', 'string', 'max:255'],
            'lotFormMemberKeys' => ['required', 'array', 'min:2'],
            'lotFormMemberKeys.*' => ['required', 'string'],
        ], [
            'lotFormMemberKeys.min' => 'Select at least 2 items to create a lot.',
            'lotFormMemberKeys.required' => 'Select at least 2 items to create a lot.',
        ], [
            'lotFormName' => 'lot name',
            'lotFormMemberKeys' => 'lot items',
        ]);

        $eligibleKeys = collect($this->availableLotMemberOptions($this->editingLotId))
            ->pluck('key')
            ->all();

        $memberKeys = array_values(array_unique(array_filter(
            $this->lotFormMemberKeys,
            fn (string $key): bool => in_array($key, $eligibleKeys, true)
        )));

        if (count($memberKeys) < 2) {
            throw ValidationException::withMessages([
                'lotFormMemberKeys' => 'Select at least 2 available items to create a lot.',
            ]);
        }

        $name = trim($this->lotFormName);

        if ($this->editingLotId !== null) {
            $this->lots = array_map(function (array $lot) use ($name, $memberKeys): array {
                if ($lot['id'] !== $this->editingLotId) {
                    return $lot;
                }

                return [
                    'id' => $lot['id'],
                    'name' => $name,
                    'member_keys' => $memberKeys,
                ];
            }, $this->lots);
        } else {
            $this->lots[] = [
                'id' => (string) Str::uuid(),
                'name' => $name,
                'member_keys' => $memberKeys,
            ];
        }

        $this->closeLotForm();
    }

    public function removeLot(string $lotId): void
    {
        $this->lots = array_values(array_filter(
            $this->lots,
            fn (array $lot): bool => $lot['id'] !== $lotId
        ));

        if ($this->editingLotId === $lotId) {
            $this->closeLotForm();
        }
    }

    public function createPurchaseRequest(DevToolsPurchaseRequestService $service): void
    {
        $this->validate([
            'departmentId' => ['required', 'exists:departments,id'],
            'requesterId' => ['required', 'exists:users,id'],
            'purpose' => ['required', 'string', 'max:1000'],
            'justification' => ['required', 'string', 'max:2000'],
            'dateNeeded' => ['nullable', 'date'],
            'landingStatus' => ['required', 'in:'.implode(',', DevToolsPurchaseRequestService::STATUSES)],
            'itemMode' => ['required', 'in:ppmp,manual'],
        ]);

        $items = $this->buildItemsPayload();

        if ($items === []) {
            throw ValidationException::withMessages([
                'items' => 'Add at least one item before creating the purchase request.',
            ]);
        }

        /** @var User $admin */
        $admin = Auth::user();

        try {
            $purchaseRequest = $service->createPurchaseRequest([
                'department_id' => $this->departmentId,
                'requester_id' => $this->requesterId,
                'purpose' => $this->purpose,
                'justification' => $this->justification,
                'date_needed' => $this->dateNeeded,
                'status' => $this->landingStatus,
                'items' => $items,
                'notify_officers' => $this->notifyOfficers,
            ], $admin);
        } catch (ValidationException $e) {
            throw $e;
        }

        $this->createdPrNumber = $purchaseRequest->pr_number;
        $this->createdPrId = $purchaseRequest->id;
        $this->createStep = 1;
        $this->purpose = '';
        $this->justification = '';
        $this->dateNeeded = null;
        $this->selectedPpmpItemIds = [];
        $this->ppmpItemOverrides = [];
        $this->manualItems = [$this->emptyManualItem()];
        $this->resetLots();

        $this->js('window.appToast({ type: "success", message: '.json_encode('Created '.$purchaseRequest->pr_number).' })');
    }

    public function startEditBudget(int $departmentId): void
    {
        $budget = DepartmentBudget::getOrCreateForDepartment($departmentId, $this->fiscalYear);

        $this->editingBudgetDepartmentId = $departmentId;
        $this->editingAllocatedBudget = (string) $budget->allocated_budget;
        $this->editingBudgetNotes = (string) ($budget->notes ?? '');
    }

    public function cancelEditBudget(): void
    {
        $this->editingBudgetDepartmentId = null;
        $this->editingAllocatedBudget = '';
        $this->editingBudgetNotes = '';
    }

    public function saveBudget(): void
    {
        $this->validate([
            'editingBudgetDepartmentId' => ['required', 'exists:departments,id'],
            'editingAllocatedBudget' => ['required', 'numeric', 'min:0'],
            'editingBudgetNotes' => ['nullable', 'string', 'max:1000'],
            'fiscalYear' => ['required', 'integer', 'min:2020', 'max:2100'],
        ]);

        DepartmentBudget::updateOrCreate(
            [
                'department_id' => $this->editingBudgetDepartmentId,
                'fiscal_year' => $this->fiscalYear,
            ],
            [
                'allocated_budget' => $this->editingAllocatedBudget,
                'notes' => $this->editingBudgetNotes ?: null,
                'set_by' => Auth::id(),
            ]
        );

        $this->cancelEditBudget();
        $this->js('window.appToast({ type: "success", message: "Budget updated." })');
    }

    public function quickSetBudget(int $amount = 1000000): void
    {
        if (! $this->departmentId) {
            return;
        }

        DepartmentBudget::updateOrCreate(
            [
                'department_id' => $this->departmentId,
                'fiscal_year' => $this->fiscalYear,
            ],
            [
                'allocated_budget' => $amount,
                'set_by' => Auth::id(),
            ]
        );

        $this->js('window.appToast({ type: "success", message: '.json_encode('Set selected department budget to ₱'.number_format($amount, 2)).' })');
    }

    public function clearReservedBudget(): void
    {
        if (! $this->departmentId || ! $this->confirmClearReserved) {
            return;
        }

        $budget = DepartmentBudget::getOrCreateForDepartment($this->departmentId, $this->fiscalYear);
        $budget->reserved_budget = 0;
        $budget->save();

        $this->confirmClearReserved = false;
        $this->js('window.appToast({ type: "warning", message: "Reserved budget cleared for testing." })');
    }

    public function importPpmp(): void
    {
        $this->validate([
            'departmentId' => ['required', 'exists:departments,id'],
            'fiscalYear' => ['required', 'integer', 'min:2020', 'max:2100'],
            'csvFile' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
        ]);

        $fileName = 'ppmp_import_devtools_'.time().'.csv';
        $filePath = $this->csvFile->storeAs('imports', $fileName);
        $fullPath = Storage::path($filePath);

        try {
            $exitCode = Artisan::call('ppmp:import-csv', [
                'file' => $fullPath,
                '--year' => $this->fiscalYear,
                '--department' => $this->departmentId,
            ]);

            $this->importOutput = Artisan::output();
            Storage::delete($filePath);
            $this->csvFile = null;

            if ($exitCode === 0) {
                $this->js('window.appToast({ type: "success", message: "PPMP imported successfully." })');
            } else {
                $this->js('window.appToast({ type: "error", message: "PPMP import failed. Check output." })');
            }
        } catch (\Throwable $e) {
            if (Storage::exists($filePath)) {
                Storage::delete($filePath);
            }

            $this->importOutput = $e->getMessage();
            $this->js('window.appToast({ type: "error", message: "PPMP import failed." })');
        }
    }

    public function validatePpmp(PpmpBudgetValidator $budgetValidator): void
    {
        if (! $this->departmentId) {
            return;
        }

        $ppmp = Ppmp::query()
            ->forDepartment($this->departmentId)
            ->forFiscalYear($this->fiscalYear)
            ->with('department')
            ->first();

        if (! $ppmp) {
            $this->js('window.appToast({ type: "error", message: "No PPMP found for this department/year." })');

            return;
        }

        if (! $budgetValidator->validatePpmpAgainstBudget($ppmp)) {
            $status = $budgetValidator->getBudgetStatus($ppmp);
            $this->js('window.appToast({ type: "error", message: '.json_encode(
                'PPMP total (₱'.number_format($status['planned'], 2).') exceeds allocated budget (₱'.number_format($status['allocated'], 2).').'
            ).' })');

            return;
        }

        $ppmp->validate((int) Auth::id());
        $this->js('window.appToast({ type: "success", message: "PPMP validated successfully." })');
    }

    public function selectPurchaseRequest(int $purchaseRequestId): void
    {
        $this->selectedPrId = $purchaseRequestId;
        $pr = PurchaseRequest::query()->find($purchaseRequestId);

        if ($pr) {
            $this->workflowTargetStatus = $pr->status;
        }
    }

    public function applyWorkflowStatus(DevToolsPurchaseRequestService $service): void
    {
        $this->validate([
            'selectedPrId' => ['required', 'exists:purchase_requests,id'],
            'workflowTargetStatus' => ['required', 'in:'.implode(',', DevToolsPurchaseRequestService::STATUSES)],
        ]);

        /** @var User $admin */
        $admin = Auth::user();
        $pr = PurchaseRequest::query()->findOrFail($this->selectedPrId);
        $service->updateStatus($pr, $this->workflowTargetStatus, $admin);

        $this->js('window.appToast({ type: "success", message: '.json_encode('Updated '.$pr->pr_number.' to '.$this->workflowTargetStatus).' })');
    }

    public function jumpPreset(string $preset, DevToolsPurchaseRequestService $service): void
    {
        $this->validate([
            'selectedPrId' => ['required', 'exists:purchase_requests,id'],
        ]);

        /** @var User $admin */
        $admin = Auth::user();
        $pr = PurchaseRequest::query()->findOrFail($this->selectedPrId);
        $service->jumpToPreset($pr, $preset, $admin);
        $this->workflowTargetStatus = DevToolsPurchaseRequestService::JUMP_PRESETS[$preset];

        $this->js('window.appToast({ type: "success", message: '.json_encode('Jumped '.$pr->pr_number.' to '.$this->workflowTargetStatus).' })');
    }

    public function render(): View
    {
        $departments = Department::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $requesters = $this->departmentId
            ? User::query()
                ->where('department_id', $this->departmentId)
                ->where('is_active', true)
                ->role(['End User', 'Dean'])
                ->orderBy('name')
                ->get(['id', 'name', 'email'])
            : collect();

        $ppmp = $this->departmentId
            ? Ppmp::query()
                ->forDepartment($this->departmentId)
                ->forFiscalYear($this->fiscalYear)
                ->with(['items.appItem'])
                ->first()
            : null;

        $departmentBudget = $this->departmentId
            ? DepartmentBudget::getOrCreateForDepartment($this->departmentId, $this->fiscalYear)
            : null;

        $budgetRows = $this->budgetRows();

        $workflowPrs = PurchaseRequest::query()
            ->with(['requester:id,name', 'department:id,name,code'])
            ->when($this->departmentId, fn ($q) => $q->where('department_id', $this->departmentId))
            ->when($this->workflowStatusFilter !== '', fn ($q) => $q->where('status', $this->workflowStatusFilter))
            ->when($this->workflowSearch !== '', function ($q) {
                $term = '%'.$this->workflowSearch.'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('pr_number', 'like', $term)
                        ->orWhere('purpose', 'like', $term);
                });
            })
            ->latest()
            ->paginate(8, pageName: 'workflowPage');

        $selectedPr = $this->selectedPrId
            ? PurchaseRequest::query()->with(['requester', 'department'])->find($this->selectedPrId)
            : null;

        $overview = $this->buildOverview($ppmp, $departmentBudget, $requesters);

        return view('livewire.dev-tools.hub', [
            'departments' => $departments,
            'requesters' => $requesters,
            'ppmp' => $ppmp,
            'departmentBudget' => $departmentBudget,
            'budgetRows' => $budgetRows,
            'workflowPrs' => $workflowPrs,
            'selectedPr' => $selectedPr,
            'overview' => $overview,
            'statuses' => DevToolsPurchaseRequestService::STATUSES,
            'jumpPresets' => DevToolsPurchaseRequestService::JUMP_PRESETS,
            'lotMemberOptions' => $this->availableLotMemberOptions($this->editingLotId),
            'ungroupedLotMemberCount' => count($this->availableLotMemberOptions()),
            'lotMemberLabels' => $this->allLotMemberLabels(),
        ]);
    }

    /**
     * Labels for every currently selectable line item (including those already in lots).
     *
     * @return array<string, string>
     */
    protected function allLotMemberLabels(): array
    {
        $labels = [];

        if ($this->itemMode === 'manual') {
            foreach ($this->manualItems as $item) {
                $key = (string) ($item['key'] ?? '');

                if ($key === '') {
                    continue;
                }

                $name = trim((string) ($item['item_name'] ?? ''));
                $labels[$key] = ($name !== '' ? $name : 'Untitled item')
                    .' · '.(int) ($item['quantity_requested'] ?? 0)
                    .' '.(string) ($item['unit_of_measure'] ?? '');
            }

            return $labels;
        }

        if ($this->selectedPpmpItemIds === []) {
            return [];
        }

        $ppmpItems = PpmpItem::query()
            ->with('appItem')
            ->whereIn('id', $this->selectedPpmpItemIds)
            ->get()
            ->keyBy('id');

        foreach ($this->selectedPpmpItemIds as $ppmpItemId) {
            /** @var PpmpItem|null $ppmpItem */
            $ppmpItem = $ppmpItems->get($ppmpItemId);

            if (! $ppmpItem?->appItem) {
                continue;
            }

            $override = $this->ppmpItemOverrides[$ppmpItemId] ?? [];
            $quantity = (int) ($override['quantity'] ?? 1);

            $labels[$this->ppmpMemberKey($ppmpItemId)] = $ppmpItem->appItem->item_name
                .' · '.$quantity
                .' '.$ppmpItem->appItem->unit_of_measure;
        }

        return $labels;
    }

    /**
     * @return list<array{key: string, item_name: string, unit_of_measure: string, quantity_requested: int|string, estimated_unit_cost: float|string, detailed_specifications: string}>
     */
    protected function emptyManualItem(): array
    {
        return [
            'key' => (string) Str::uuid(),
            'item_name' => '',
            'unit_of_measure' => 'pcs',
            'quantity_requested' => 1,
            'estimated_unit_cost' => 100,
            'detailed_specifications' => '',
        ];
    }

    protected function resetLots(): void
    {
        $this->lots = [];
        $this->closeLotForm();
    }

    protected function ppmpMemberKey(int $ppmpItemId): string
    {
        return 'ppmp:'.$ppmpItemId;
    }

    protected function removeMemberKeyFromLots(string $memberKey): void
    {
        $this->lots = array_values(array_filter(array_map(function (array $lot) use ($memberKey): array {
            $lot['member_keys'] = array_values(array_filter(
                $lot['member_keys'],
                fn (string $key): bool => $key !== $memberKey
            ));

            return $lot;
        }, $this->lots), fn (array $lot): bool => count($lot['member_keys']) >= 2));

        $this->lotFormMemberKeys = array_values(array_filter(
            $this->lotFormMemberKeys,
            fn (string $key): bool => $key !== $memberKey
        ));
    }

    /**
     * Items eligible for the lot form: ungrouped, or already in the lot being edited.
     *
     * @return list<array{key: string, label: string}>
     */
    protected function availableLotMemberOptions(?string $editingLotId = null): array
    {
        $assignedElsewhere = [];

        foreach ($this->lots as $lot) {
            if ($editingLotId !== null && $lot['id'] === $editingLotId) {
                continue;
            }

            foreach ($lot['member_keys'] as $key) {
                $assignedElsewhere[$key] = true;
            }
        }

        $options = [];

        if ($this->itemMode === 'manual') {
            foreach ($this->manualItems as $item) {
                $key = (string) ($item['key'] ?? '');

                if ($key === '' || isset($assignedElsewhere[$key])) {
                    continue;
                }

                $name = trim((string) ($item['item_name'] ?? ''));
                $options[] = [
                    'key' => $key,
                    'label' => ($name !== '' ? $name : 'Untitled item')
                        .' · '.(int) ($item['quantity_requested'] ?? 0)
                        .' '.(string) ($item['unit_of_measure'] ?? ''),
                ];
            }

            return $options;
        }

        if ($this->selectedPpmpItemIds === []) {
            return [];
        }

        $ppmpItems = PpmpItem::query()
            ->with('appItem')
            ->whereIn('id', $this->selectedPpmpItemIds)
            ->get()
            ->keyBy('id');

        foreach ($this->selectedPpmpItemIds as $ppmpItemId) {
            $key = $this->ppmpMemberKey($ppmpItemId);

            if (isset($assignedElsewhere[$key])) {
                continue;
            }

            /** @var PpmpItem|null $ppmpItem */
            $ppmpItem = $ppmpItems->get($ppmpItemId);

            if (! $ppmpItem?->appItem) {
                continue;
            }

            $override = $this->ppmpItemOverrides[$ppmpItemId] ?? [];
            $quantity = (int) ($override['quantity'] ?? 1);

            $options[] = [
                'key' => $key,
                'label' => $ppmpItem->appItem->item_name
                    .' · '.$quantity
                    .' '.$ppmpItem->appItem->unit_of_measure,
            ];
        }

        return $options;
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function buildItemsPayload(): array
    {
        $keyedItems = $this->buildKeyedLineItems();

        if ($keyedItems === []) {
            return [];
        }

        $this->validateLotsAgainstItems(array_keys($keyedItems));

        $payload = [];
        $assignedKeys = [];

        foreach ($this->lots as $lot) {
            $memberKeys = array_values(array_filter(
                $lot['member_keys'],
                fn (string $key): bool => isset($keyedItems[$key])
            ));

            if (count($memberKeys) < 2) {
                throw ValidationException::withMessages([
                    'lots' => 'Each lot needs at least 2 items. Remove incomplete lots or add more items.',
                ]);
            }

            $lotTotal = 0.0;

            foreach ($memberKeys as $memberKey) {
                $item = $keyedItems[$memberKey];
                $lotTotal += (float) $item['quantity_requested'] * (float) $item['estimated_unit_cost'];
            }

            $lotIndex = count($payload);

            $payload[] = [
                'ppmp_item_id' => null,
                'item_code' => null,
                'item_name' => $lot['name'],
                'detailed_specifications' => null,
                'unit_of_measure' => 'lot',
                'quantity_requested' => 1,
                'estimated_unit_cost' => round($lotTotal, 2),
                'is_lot' => true,
                'lot_name' => $lot['name'],
            ];

            foreach ($memberKeys as $memberKey) {
                $child = $keyedItems[$memberKey];
                $child['is_lot'] = false;
                $child['parent_lot_index'] = $lotIndex;
                $payload[] = $child;
                $assignedKeys[$memberKey] = true;
            }
        }

        foreach ($keyedItems as $key => $item) {
            if (isset($assignedKeys[$key])) {
                continue;
            }

            $item['is_lot'] = false;
            $payload[] = $item;
        }

        return $payload;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected function buildKeyedLineItems(): array
    {
        if ($this->itemMode === 'manual') {
            $this->validate([
                'manualItems' => ['required', 'array', 'min:1'],
                'manualItems.*.item_name' => ['required', 'string', 'max:255'],
                'manualItems.*.unit_of_measure' => ['required', 'string', 'max:50'],
                'manualItems.*.quantity_requested' => ['required', 'integer', 'min:1'],
                'manualItems.*.estimated_unit_cost' => ['required', 'numeric', 'min:0.01'],
                'manualItems.*.detailed_specifications' => ['nullable', 'string', 'max:2000'],
            ]);

            $items = [];

            foreach ($this->manualItems as $item) {
                $key = (string) ($item['key'] ?? '');

                if ($key === '') {
                    continue;
                }

                $items[$key] = [
                    'ppmp_item_id' => null,
                    'item_code' => null,
                    'item_name' => $item['item_name'],
                    'detailed_specifications' => $item['detailed_specifications'] ?: null,
                    'unit_of_measure' => $item['unit_of_measure'],
                    'quantity_requested' => (int) $item['quantity_requested'],
                    'estimated_unit_cost' => (float) $item['estimated_unit_cost'],
                    'is_lot' => false,
                ];
            }

            return $items;
        }

        if ($this->selectedPpmpItemIds === []) {
            return [];
        }

        $ppmpItems = PpmpItem::query()
            ->with('appItem')
            ->whereIn('id', $this->selectedPpmpItemIds)
            ->get()
            ->keyBy('id');

        $items = [];

        foreach ($this->selectedPpmpItemIds as $ppmpItemId) {
            /** @var PpmpItem|null $ppmpItem */
            $ppmpItem = $ppmpItems->get($ppmpItemId);

            if (! $ppmpItem || ! $ppmpItem->appItem) {
                continue;
            }

            $override = $this->ppmpItemOverrides[$ppmpItemId] ?? [];
            $quantity = (int) ($override['quantity'] ?? 1);
            $unitCost = (float) ($override['unit_cost'] ?? $ppmpItem->estimated_unit_cost);

            if ($quantity < 1 || $unitCost <= 0) {
                throw ValidationException::withMessages([
                    'ppmpItemOverrides.'.$ppmpItemId.'.quantity' => 'Each selected PPMP item needs a valid quantity and unit cost.',
                ]);
            }

            $items[$this->ppmpMemberKey($ppmpItemId)] = [
                'ppmp_item_id' => $ppmpItem->id,
                'item_code' => $ppmpItem->appItem->item_code,
                'item_name' => $ppmpItem->appItem->item_name,
                'detailed_specifications' => $ppmpItem->appItem->specifications,
                'unit_of_measure' => $ppmpItem->appItem->unit_of_measure,
                'quantity_requested' => $quantity,
                'estimated_unit_cost' => $unitCost,
                'is_lot' => false,
            ];
        }

        return $items;
    }

    /**
     * @param  list<string>  $validKeys
     */
    protected function validateLotsAgainstItems(array $validKeys): void
    {
        if ($this->lots === []) {
            return;
        }

        $this->validate([
            'lots' => ['array'],
            'lots.*.name' => ['required', 'string', 'max:255'],
            'lots.*.member_keys' => ['required', 'array', 'min:2'],
        ], [], [
            'lots.*.name' => 'lot name',
            'lots.*.member_keys' => 'lot items',
        ]);

        $seen = [];

        foreach ($this->lots as $lot) {
            foreach ($lot['member_keys'] as $memberKey) {
                if (! in_array($memberKey, $validKeys, true)) {
                    throw ValidationException::withMessages([
                        'lots' => 'A lot references an item that is no longer selected. Edit or remove that lot.',
                    ]);
                }

                if (isset($seen[$memberKey])) {
                    throw ValidationException::withMessages([
                        'lots' => 'An item cannot belong to more than one lot.',
                    ]);
                }

                $seen[$memberKey] = true;
            }
        }
    }

    /**
     * @return Collection<int, object>
     */
    protected function budgetRows(): Collection
    {
        return Department::query()
            ->where('is_active', true)
            ->when($this->budgetSearch !== '', function ($query) {
                $term = '%'.$this->budgetSearch.'%';
                $query->where(function ($inner) use ($term) {
                    $inner->where('name', 'like', $term)
                        ->orWhere('code', 'like', $term);
                });
            })
            ->orderBy('name')
            ->with(['budgets' => fn ($q) => $q->where('fiscal_year', $this->fiscalYear)])
            ->get()
            ->map(function (Department $department) {
                $budget = $department->budgets->first();

                return (object) [
                    'department' => $department,
                    'allocated' => (float) ($budget?->allocated_budget ?? 0),
                    'reserved' => (float) ($budget?->reserved_budget ?? 0),
                    'utilized' => (float) ($budget?->utilized_budget ?? 0),
                    'available' => $budget
                        ? $budget->getAvailableBudget()
                        : 0.0,
                    'notes' => $budget?->notes,
                    'has_budget' => $budget !== null,
                ];
            });
    }

    /**
     * @param  Collection<int, User>  $requesters
     * @return array{
     *     has_department: bool,
     *     has_budget: bool,
     *     budget_allocated: float,
     *     has_ppmp: bool,
     *     ppmp_validated: bool,
     *     requester_count: int,
     *     recent_pr_count: int
     * }
     */
    protected function buildOverview(?Ppmp $ppmp, ?DepartmentBudget $departmentBudget, Collection $requesters): array
    {
        $recentPrCount = $this->departmentId
            ? PurchaseRequest::query()
                ->where('department_id', $this->departmentId)
                ->whereYear('created_at', $this->fiscalYear)
                ->count()
            : 0;

        return [
            'has_department' => $this->departmentId !== null,
            'has_budget' => $departmentBudget !== null && (float) $departmentBudget->allocated_budget > 0,
            'budget_allocated' => (float) ($departmentBudget?->allocated_budget ?? 0),
            'has_ppmp' => $ppmp !== null,
            'ppmp_validated' => $ppmp?->status === 'validated',
            'requester_count' => $requesters->count(),
            'recent_pr_count' => $recentPrCount,
        ];
    }
}
