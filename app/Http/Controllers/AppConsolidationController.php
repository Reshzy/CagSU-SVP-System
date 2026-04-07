<?php

namespace App\Http\Controllers;

use App\Services\AppConsolidationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class AppConsolidationController extends Controller
{
    public function __construct(
        protected AppConsolidationService $appConsolidationService
    ) {}

    /**
     * Show read-only consolidated APP from validated PPMPs.
     */
    public function index(Request $request): View
    {
        $fiscalYear = (int) $request->input('fiscal_year', date('Y'));
        $consolidatedItems = $this->appConsolidationService->getConsolidatedItems($fiscalYear);

        return view('bac.app.index', [
            'fiscalYear' => $fiscalYear,
            'groupedItems' => $consolidatedItems->groupBy('category'),
            'stats' => $this->appConsolidationService->getStats($fiscalYear, $consolidatedItems),
        ]);
    }
}
