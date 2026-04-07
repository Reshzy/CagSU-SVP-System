<?php

namespace App\Services;

use App\Models\Ppmp;
use App\Models\PpmpItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AppConsolidationService
{
    /**
     * Build consolidated APP rows from validated PPMP items.
     */
    public function getConsolidatedItems(int $fiscalYear): Collection
    {
        return PpmpItem::query()
            ->select([
                'app_items.id as app_item_id',
                'app_items.category',
                'app_items.item_code',
                'app_items.item_name',
                'app_items.unit_of_measure',
                DB::raw('SUM(ppmp_items.q1_quantity) as q1_quantity'),
                DB::raw('SUM(ppmp_items.q2_quantity) as q2_quantity'),
                DB::raw('SUM(ppmp_items.q3_quantity) as q3_quantity'),
                DB::raw('SUM(ppmp_items.q4_quantity) as q4_quantity'),
                DB::raw('SUM(ppmp_items.total_quantity) as total_quantity'),
                DB::raw('SUM(ppmp_items.estimated_total_cost) as estimated_total_cost'),
                DB::raw('COUNT(DISTINCT ppmps.department_id) as department_count'),
            ])
            ->join('ppmps', 'ppmps.id', '=', 'ppmp_items.ppmp_id')
            ->join('app_items', 'app_items.id', '=', 'ppmp_items.app_item_id')
            ->where('ppmps.fiscal_year', $fiscalYear)
            ->where('ppmps.status', 'validated')
            ->groupBy('app_items.id', 'app_items.category', 'app_items.item_code', 'app_items.item_name', 'app_items.unit_of_measure')
            ->orderBy('app_items.category')
            ->orderBy('app_items.item_name')
            ->get();
    }

    /**
     * Return APP-level consolidation statistics.
     *
     * @return array<string, float|int>
     */
    public function getStats(int $fiscalYear, Collection $consolidatedItems): array
    {
        $validatedPpmpCount = Ppmp::query()
            ->where('fiscal_year', $fiscalYear)
            ->where('status', 'validated')
            ->count();

        $departmentCount = Ppmp::query()
            ->where('fiscal_year', $fiscalYear)
            ->where('status', 'validated')
            ->distinct('department_id')
            ->count('department_id');

        return [
            'validated_ppmps' => $validatedPpmpCount,
            'departments_included' => $departmentCount,
            'total_items' => $consolidatedItems->count(),
            'grand_total_cost' => (float) $consolidatedItems->sum('estimated_total_cost'),
        ];
    }
}
