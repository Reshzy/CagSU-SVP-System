<?php

namespace App\Http\Controllers;

use App\Models\AppItem;
use App\Models\Ppmp;
use App\Models\PpmpItem;
use App\Services\PpmpBudgetValidator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PpmpController extends Controller
{
    public function __construct(
        protected PpmpBudgetValidator $budgetValidator
    ) {}

    /**
     * Display department's PPMP
     */
    public function index(): View|RedirectResponse
    {
        $user = Auth::user();

        if (! $user->department_id) {
            return redirect()->route('dashboard')
                ->withErrors(['error' => 'You must be assigned to a department to access PPMP.']);
        }

        $fiscalYear = date('Y');
        $ppmp = Ppmp::getOrCreateForDepartment($user->department_id, $fiscalYear);

        // Load items with APP item details
        $ppmp->load(['items.appItem', 'department']);

        // Get budget status
        $budgetStatus = $this->budgetValidator->getBudgetStatus($ppmp);

        return view('ppmp.index', [
            'ppmp' => $ppmp,
            'budgetStatus' => $budgetStatus,
            'fiscalYear' => $fiscalYear,
        ]);
    }

    /**
     * Show PS DBMS reference items for PPMP selection.
     */
    public function create(): View|RedirectResponse
    {
        $user = Auth::user();

        if (! $user->department_id) {
            return redirect()->route('dashboard')
                ->withErrors(['error' => 'You must be assigned to a department to create PPMP.']);
        }

        $fiscalYear = date('Y');
        $ppmp = Ppmp::getOrCreateForDepartment($user->department_id, $fiscalYear);

        // Get PS DBMS reference items
        $categories = AppItem::getCategories($fiscalYear);
        $appItems = AppItem::active()
            ->forFiscalYear($fiscalYear)
            ->orderBy('category')
            ->orderBy('item_name')
            ->get()
            ->groupBy('category');

        // Get existing PPMP items
        $existingItems = $ppmp->items()
            ->with('appItem')
            ->get()
            ->keyBy('app_item_id');

        // Get budget status
        $budgetStatus = $this->budgetValidator->getBudgetStatus($ppmp);

        return view('ppmp.create', [
            'ppmp' => $ppmp,
            'categories' => $categories,
            'appItems' => $appItems,
            'existingItems' => $existingItems,
            'budgetStatus' => $budgetStatus,
            'fiscalYear' => $fiscalYear,
        ]);
    }

    /**
     * Store PPMP items
     */
    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if (! $user->department_id) {
            return redirect()->route('dashboard')
                ->withErrors(['error' => 'You must be assigned to a department.']);
        }

        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.app_item_id' => ['required', 'exists:app_items,id'],
            'items.*.q1_quantity' => ['required', 'integer', 'min:0'],
            'items.*.q2_quantity' => ['required', 'integer', 'min:0'],
            'items.*.q3_quantity' => ['required', 'integer', 'min:0'],
            'items.*.q4_quantity' => ['required', 'integer', 'min:0'],
            'items.*.custom_unit_price' => ['nullable', 'numeric', 'min:0.01'],
        ]);

        $fiscalYear = date('Y');
        $ppmp = Ppmp::getOrCreateForDepartment($user->department_id, $fiscalYear);

        DB::transaction(function () use ($ppmp, $validated) {
            // Delete existing items
            $ppmp->items()->delete();

            // Create new items
            foreach ($validated['items'] as $itemData) {
                $appItem = AppItem::findOrFail($itemData['app_item_id']);

                $totalQuantity = $itemData['q1_quantity'] +
                               $itemData['q2_quantity'] +
                               $itemData['q3_quantity'] +
                               $itemData['q4_quantity'];

                // Skip if no quantity
                if ($totalQuantity === 0) {
                    continue;
                }

                // Use custom unit price if provided, otherwise use PS DBMS reference price
                $estimatedUnitCost = $itemData['custom_unit_price'] ?? $appItem->unit_price;

                // Validate that we have a price
                if ($estimatedUnitCost === null || $estimatedUnitCost <= 0) {
                    throw new \Exception("Item '{$appItem->item_name}' requires a custom price.");
                }

                $estimatedTotalCost = $totalQuantity * $estimatedUnitCost;

                PpmpItem::create([
                    'ppmp_id' => $ppmp->id,
                    'app_item_id' => $appItem->id,
                    'q1_quantity' => $itemData['q1_quantity'],
                    'q2_quantity' => $itemData['q2_quantity'],
                    'q3_quantity' => $itemData['q3_quantity'],
                    'q4_quantity' => $itemData['q4_quantity'],
                    'total_quantity' => $totalQuantity,
                    'estimated_unit_cost' => $estimatedUnitCost,
                    'estimated_total_cost' => $estimatedTotalCost,
                ]);
            }

            // Update PPMP total
            $ppmp->total_estimated_cost = $ppmp->calculateTotalCost();
            $ppmp->save();
        });

        return redirect()
            ->route('ppmp.index')
            ->with('success', 'PPMP items saved successfully!');
    }

    /**
     * Edit PPMP
     */
    public function edit(Ppmp $ppmp): View|RedirectResponse
    {
        $user = Auth::user();

        if ($ppmp->department_id !== $user->department_id) {
            return redirect()->route('ppmp.index')
                ->withErrors(['error' => 'You can only edit your own department PPMP.']);
        }

        return $this->create();
    }

    /**
     * Update PPMP
     */
    public function update(Request $request, Ppmp $ppmp): RedirectResponse
    {
        $user = Auth::user();

        if ($ppmp->department_id !== $user->department_id) {
            return redirect()->route('ppmp.index')
                ->withErrors(['error' => 'You can only update your own department PPMP.']);
        }

        return $this->store($request);
    }

    /**
     * Validate PPMP
     */
    public function validate(Ppmp $ppmp): RedirectResponse
    {
        $user = Auth::user();

        if ($ppmp->department_id !== $user->department_id) {
            return redirect()->route('ppmp.index')
                ->withErrors(['error' => 'You can only validate your own department PPMP.']);
        }

        if (! $this->budgetValidator->validatePpmpAgainstBudget($ppmp)) {
            $budgetStatus = $this->budgetValidator->getBudgetStatus($ppmp);

            return back()
                ->withErrors([
                    'budget' => 'PPMP total (₱'.number_format($budgetStatus['planned'], 2).
                               ') exceeds allocated budget (₱'.number_format($budgetStatus['allocated'], 2).').',
                ]);
        }

        $ppmp->validate($user->id);

        return redirect()
            ->route('ppmp.index')
            ->with('success', 'PPMP validated successfully!');
    }

    /**
     * Show the PPMP import form
     */
    public function importForm(): View|RedirectResponse
    {
        $user = Auth::user();

        if (! $user->department_id) {
            return redirect()->route('dashboard')
                ->withErrors(['error' => 'You must be assigned to a department to import a PPMP.']);
        }

        return view('ppmp.import');
    }

    /**
     * Process a PPMP CSV import
     */
    public function processImport(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if (! $user->department_id) {
            return redirect()->route('dashboard')
                ->withErrors(['error' => 'You must be assigned to a department to import a PPMP.']);
        }

        $validated = $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
            'fiscal_year' => ['required', 'integer', 'min:2020', 'max:2100'],
        ]);

        $file = $request->file('csv_file');
        $fileName = 'ppmp_import_'.time().'.csv';
        $filePath = $file->storeAs('imports', $fileName);
        $fullPath = Storage::path($filePath);

        try {
            $exitCode = Artisan::call('ppmp:import-csv', [
                'file' => $fullPath,
                '--year' => $validated['fiscal_year'],
                '--department' => $user->department_id,
            ]);

            $output = Artisan::output();

            Storage::delete($filePath);

            if ($exitCode === 0) {
                return redirect()
                    ->route('ppmp.index')
                    ->with('success', 'PPMP imported successfully!')
                    ->with('import_output', $output);
            }

            return back()
                ->withInput()
                ->withErrors(['csv_file' => 'Import failed. Please check the file format.'])
                ->with('import_output', $output);
        } catch (\Exception $e) {
            if (Storage::exists($filePath)) {
                Storage::delete($filePath);
            }

            return back()
                ->withInput()
                ->withErrors(['csv_file' => 'Import failed: '.$e->getMessage()]);
        }
    }

    /**
     * Show PPMP summary
     */
    public function summary(Ppmp $ppmp): View|RedirectResponse
    {
        $user = Auth::user();

        if ($ppmp->department_id !== $user->department_id) {
            return redirect()->route('ppmp.index')
                ->withErrors(['error' => 'You can only view your own department PPMP.']);
        }

        $ppmp->load(['items.appItem', 'department']);

        $budgetStatus = $this->budgetValidator->getBudgetStatus($ppmp);

        // Group items by category
        $itemsByCategory = $ppmp->items->groupBy(function ($item) {
            return $item->appItem->category;
        });

        return view('ppmp.summary', [
            'ppmp' => $ppmp,
            'itemsByCategory' => $itemsByCategory,
            'budgetStatus' => $budgetStatus,
        ]);
    }
}
