<?php

namespace App\Http\Controllers;

use App\Models\AppItem;
use App\Models\Ppmp;
use App\Models\PpmpItem;
use App\Services\PpmpBudgetValidator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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

        if (!$user->department_id) {
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
     * Show APP items for selection
     */
    public function create(): View|RedirectResponse
    {
        $user = Auth::user();

        if (!$user->department_id) {
            return redirect()->route('dashboard')
                ->withErrors(['error' => 'You must be assigned to a department to create PPMP.']);
        }

        $fiscalYear = date('Y');
        $ppmp = Ppmp::getOrCreateForDepartment($user->department_id, $fiscalYear);

        // Get APP items
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

        if (!$user->department_id) {
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

                $estimatedUnitCost = $appItem->unit_price;
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

        if (!$this->budgetValidator->validatePpmpAgainstBudget($ppmp)) {
            $budgetStatus = $this->budgetValidator->getBudgetStatus($ppmp);
            
            return back()
                ->withErrors([
                    'budget' => 'PPMP total (₱' . number_format($budgetStatus['planned'], 2) . 
                               ') exceeds allocated budget (₱' . number_format($budgetStatus['allocated'], 2) . ').'
                ]);
        }

        $ppmp->validate($user->id);

        return redirect()
            ->route('ppmp.index')
            ->with('success', 'PPMP validated successfully!');
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
