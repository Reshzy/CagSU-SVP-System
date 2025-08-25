<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\PurchaseRequest;
use App\Models\Supplier;
use App\Models\Quotation;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;

class ReportsController extends Controller
{
    public function pr(Request $request): View
    {
        $filters = [
            'status' => $request->string('status')->toString(),
            'department_id' => $request->integer('department_id') ?: null,
            'date_from' => $request->date('date_from'),
            'date_to' => $request->date('date_to'),
        ];

        $query = PurchaseRequest::with(['requester', 'department'])->latest();

        if ($filters['status']) {
            $query->where('status', $filters['status']);
        }
        if ($filters['department_id']) {
            $query->where('department_id', $filters['department_id']);
        }
        if ($filters['date_from']) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if ($filters['date_to']) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        $requests = $query->paginate(20)->withQueryString();
        $departments = Department::orderBy('name')->get();

        return view('reports.pr', compact('requests', 'filters', 'departments'));
    }

    public function prExport(Request $request)
    {
        $query = PurchaseRequest::with(['requester', 'department'])->orderBy('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->integer('department_id'));
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date('date_to'));
        }

        $filename = 'purchase_requests_report_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'no-store, no-cache',
        ];

        $callback = function () use ($query) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'PR Number', 'Created At', 'Requester', 'Department', 'Purpose', 'Date Needed', 'Priority', 'Estimated Total', 'Status'
            ]);

            $query->chunk(500, function ($chunk) use ($handle) {
                foreach ($chunk as $pr) {
                    fputcsv($handle, [
                        $pr->pr_number,
                        optional($pr->created_at)->format('Y-m-d H:i'),
                        $pr->requester?->name,
                        $pr->department?->name,
                        $pr->purpose,
                        optional($pr->date_needed)->format('Y-m-d'),
                        $pr->priority,
                        number_format((float)$pr->estimated_total, 2, '.', ''),
                        $pr->status,
                    ]);
                }
            });

            fclose($handle);
        };

        return Response::stream($callback, 200, $headers);
    }

    public function analytics(Request $request): View
    {
        // Monthly PR counts (last 12 months)
        $labels = collect(range(0,11))->map(function ($i) {
            return now()->startOfMonth()->subMonths(11 - $i)->format('Y-m');
        });

        $counts = $labels->map(function ($ym) {
            return PurchaseRequest::whereBetween('created_at', [
                \Carbon\Carbon::createFromFormat('Y-m', $ym)->startOfMonth(),
                \Carbon\Carbon::createFromFormat('Y-m', $ym)->endOfMonth(),
            ])->count();
        });

        // Average cycle time per month (days) for completed PRs
        $cycle = $labels->map(function ($ym) {
            $start = \Carbon\Carbon::createFromFormat('Y-m', $ym)->startOfMonth();
            $end = \Carbon\Carbon::createFromFormat('Y-m', $ym)->endOfMonth();
            return (float) PurchaseRequest::whereBetween('completed_at', [$start, $end])
                ->whereNotNull('submitted_at')
                ->whereNotNull('completed_at')
                ->selectRaw('AVG(DATEDIFF(completed_at, submitted_at)) as avg_days')
                ->value('avg_days');
        });

        return view('reports.analytics', [
            'labels' => $labels,
            'counts' => $counts,
            'cycle' => $cycle,
        ]);
    }

    public function suppliers(Request $request): View
    {
        $suppliers = Supplier::orderBy('business_name')->get();

        $rows = $suppliers->map(function (Supplier $s) {
            $quotes = Quotation::where('supplier_id', $s->id);
            $totalQuotes = (clone $quotes)->count();
            $awards = (clone $quotes)->where('is_winning_bid', true);
            $awardsCount = (clone $awards)->count();
            $awardedTotal = (clone $awards)->sum('total_amount');

            $poQuery = PurchaseOrder::where('supplier_id', $s->id);
            $poCount = (clone $poQuery)->count();
            $poDelivered = (clone $poQuery)->whereIn('status', ['delivered','completed'])->count();
            $poCompleted = (clone $poQuery)->where('status', 'completed')->count();
            $poTotal = (clone $poQuery)->sum('total_amount');

            $winRate = $totalQuotes > 0 ? round(($awardsCount / $totalQuotes) * 100, 1) : null;

            return [
                'supplier' => $s,
                'total_quotes' => $totalQuotes,
                'awards' => $awardsCount,
                'win_rate' => $winRate,
                'awarded_total' => (float) $awardedTotal,
                'po_count' => $poCount,
                'po_delivered' => $poDelivered,
                'po_completed' => $poCompleted,
                'po_total' => (float) $poTotal,
            ];
        });

        return view('reports.suppliers', ['rows' => $rows]);
    }

    public function suppliersExport(Request $request)
    {
        $rows = $this->suppliers($request)->getData()['rows'];

        $filename = 'supplier_performance_' . now()->format('Ymd_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'no-store, no-cache',
        ];

        $callback = function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Supplier','Quotes','Awards','Win Rate %','Awarded Value','POs','Completed POs','PO Value']);
            foreach ($rows as $r) {
                fputcsv($handle, [
                    $r['supplier']->business_name,
                    $r['total_quotes'],
                    $r['awards'],
                    $r['win_rate'],
                    number_format($r['awarded_total'], 2, '.', ''),
                    $r['po_count'],
                    $r['po_completed'],
                    number_format($r['po_total'], 2, '.', ''),
                ]);
            }
            fclose($handle);
        };

        return \Illuminate\Support\Facades\Response::stream($callback, 200, $headers);
    }

    public function budget(Request $request): View
    {
        $departments = Department::orderBy('name')->get();

        $rows = $departments->map(function (Department $d) {
            $prTotal = PurchaseRequest::where('department_id', $d->id)->sum('estimated_total');
            $prCount = PurchaseRequest::where('department_id', $d->id)->count();
            $poTotal = PurchaseOrder::whereIn('purchase_request_id', function ($q) use ($d) {
                $q->select('id')->from('purchase_requests')->where('department_id', $d->id);
            })->sum('total_amount');
            $poCount = PurchaseOrder::whereIn('purchase_request_id', function ($q) use ($d) {
                $q->select('id')->from('purchase_requests')->where('department_id', $d->id);
            })->count();
            $poCompleted = PurchaseOrder::whereIn('purchase_request_id', function ($q) use ($d) {
                $q->select('id')->from('purchase_requests')->where('department_id', $d->id);
            })->where('status', 'completed')->count();

            return [
                'department' => $d,
                'pr_total' => (float) $prTotal,
                'pr_count' => $prCount,
                'po_total' => (float) $poTotal,
                'po_count' => $poCount,
                'po_completed' => $poCompleted,
                'utilization_rate' => $prTotal > 0 ? round(($poTotal / $prTotal) * 100, 1) : null,
            ];
        });

        $totals = [
            'pr_total' => (float) $rows->sum('pr_total'),
            'po_total' => (float) $rows->sum('po_total'),
            'pr_count' => (int) $rows->sum('pr_count'),
            'po_count' => (int) $rows->sum('po_count'),
        ];

        return view('reports.budget', compact('rows', 'totals'));
    }

    public function budgetExport(Request $request)
    {
        $view = $this->budget($request);
        $rows = $view->getData()['rows'];
        $totals = $view->getData()['totals'];

        $filename = 'budget_utilization_' . now()->format('Ymd_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'no-store, no-cache',
        ];

        $callback = function () use ($rows, $totals) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Department','PR Count','PR Total','PO Count','PO Total','Utilization %']);
            foreach ($rows as $r) {
                fputcsv($handle, [
                    $r['department']->name,
                    $r['pr_count'],
                    number_format($r['pr_total'], 2, '.', ''),
                    $r['po_count'],
                    number_format($r['po_total'], 2, '.', ''),
                    $r['utilization_rate'],
                ]);
            }
            fputcsv($handle, []);
            fputcsv($handle, ['Totals','', number_format($totals['pr_total'], 2, '.', ''), '', number_format($totals['po_total'], 2, '.', ''), '']);
            fclose($handle);
        };

        return \Illuminate\Support\Facades\Response::stream($callback, 200, $headers);
    }

    public function custom(Request $request): View
    {
        $available = [
            'pr_number' => 'PR Number',
            'created_at' => 'Created At',
            'requester' => 'Requester',
            'department' => 'Department',
            'purpose' => 'Purpose',
            'date_needed' => 'Date Needed',
            'priority' => 'Priority',
            'estimated_total' => 'Estimated Total',
            'status' => 'Status',
        ];

        $selected = $request->input('columns', ['pr_number','created_at','requester','department','estimated_total','status']);

        $query = PurchaseRequest::with(['requester','department'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->integer('department_id'));
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date('date_to'));
        }

        $requests = $query->paginate(25)->withQueryString();
        $departments = Department::orderBy('name')->get();
        $allStatuses = ['draft','submitted','supply_office_review','budget_office_review','ceo_approval','bac_evaluation','bac_approved','po_generation','po_approved','supplier_processing','delivered','completed','cancelled','rejected'];

        return view('reports.custom', compact('available','selected','requests','departments','allStatuses'));
    }

    public function customExport(Request $request)
    {
        $available = [
            'pr_number' => 'PR Number',
            'created_at' => 'Created At',
            'requester' => 'Requester',
            'department' => 'Department',
            'purpose' => 'Purpose',
            'date_needed' => 'Date Needed',
            'priority' => 'Priority',
            'estimated_total' => 'Estimated Total',
            'status' => 'Status',
        ];
        $selected = array_values(array_intersect(array_keys($available), (array)$request->input('columns', [])));
        if (empty($selected)) {
            $selected = ['pr_number','created_at','requester','department','estimated_total','status'];
        }

        $query = PurchaseRequest::with(['requester','department'])->latest();
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->integer('department_id'));
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date('date_to'));
        }

        $filename = 'custom_report_' . now()->format('Ymd_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'no-store, no-cache',
        ];

        $callback = function () use ($query, $selected, $available) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, array_map(fn($k) => $available[$k], $selected));
            $query->chunk(500, function ($chunk) use ($handle, $selected) {
                foreach ($chunk as $pr) {
                    $row = [];
                    foreach ($selected as $col) {
                        $row[] = match ($col) {
                            'pr_number' => $pr->pr_number,
                            'created_at' => optional($pr->created_at)->format('Y-m-d H:i'),
                            'requester' => $pr->requester?->name,
                            'department' => $pr->department?->name,
                            'purpose' => $pr->purpose,
                            'date_needed' => optional($pr->date_needed)->format('Y-m-d'),
                            'priority' => $pr->priority,
                            'estimated_total' => number_format((float)$pr->estimated_total, 2, '.', ''),
                            'status' => $pr->status,
                            default => ''
                        };
                    }
                    fputcsv($handle, $row);
                }
            });
            fclose($handle);
        };

        return Response::stream($callback, 200, $headers);
    }
}


