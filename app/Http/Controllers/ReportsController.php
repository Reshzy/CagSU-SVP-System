<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\PurchaseRequest;
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
}


