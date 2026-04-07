<?php

namespace App\Livewire\Budget;

use App\Models\Department;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class DepartmentBudgetsTable extends Component
{
    use WithPagination;

    private const SORTABLE_FIELDS = [
        'department',
        'code',
        'allocated',
        'utilized',
        'reserved',
        'available',
        'utilization',
    ];

    private const TOGGLEABLE_COLUMNS = [
        'code',
        'allocated',
        'utilized',
        'reserved',
        'available',
        'utilization',
    ];

    private const PER_PAGE_OPTIONS = [10, 20, 50];

    private const DEFAULT_VISIBLE_COLUMNS = [
        'code',
        'allocated',
        'utilized',
        'reserved',
        'available',
        'utilization',
    ];

    public int $fiscalYear;

    #[Url(as: 'search')]
    public string $search = '';

    #[Url(as: 'sort')]
    public string $sortField = 'department';

    #[Url(as: 'direction')]
    public string $sortDirection = 'asc';

    #[Url(as: 'per_page')]
    public int $perPage = 20;

    /** @var list<string> */
    public array $visibleColumns = self::DEFAULT_VISIBLE_COLUMNS;

    public function mount(int $fiscalYear): void
    {
        $this->fiscalYear = $fiscalYear;
        $this->normalizeSort();
        $this->normalizePerPage();
        $this->visibleColumns = $this->sanitizeVisibleColumns($this->visibleColumns);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->normalizePerPage();
    }

    public function sortBy(string $field): void
    {
        if (! in_array($field, self::SORTABLE_FIELDS, true)) {
            return;
        }

        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = in_array($field, ['available', 'utilization'], true) ? 'desc' : 'asc';
        }

        $this->resetPage();
    }

    public function toggleColumn(string $column): void
    {
        if (! in_array($column, self::TOGGLEABLE_COLUMNS, true)) {
            return;
        }

        if (in_array($column, $this->visibleColumns, true)) {
            $next = array_values(array_filter(
                $this->visibleColumns,
                fn (string $visibleColumn): bool => $visibleColumn !== $column
            ));

            if ($next === []) {
                return;
            }

            $this->visibleColumns = $next;

            return;
        }

        $this->visibleColumns[] = $column;
        $this->visibleColumns = array_values(array_unique($this->visibleColumns));
    }

    public function isColumnVisible(string $column): bool
    {
        return in_array($column, $this->visibleColumns, true);
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->resetPage();
    }

    public function render(): View
    {
        $query = $this->baseQuery();
        $this->applySearch($query);
        $this->applySorting($query);

        $summaryRows = (clone $query)->get();
        $departments = $query->paginate($this->perPage);

        return view('livewire.budget.department-budgets-table', [
            'columnLabels' => [
                'code' => 'Code',
                'allocated' => 'Allocated',
                'utilized' => 'Utilized',
                'reserved' => 'Reserved',
                'available' => 'Available',
                'utilization' => 'Utilization',
            ],
            'perPageOptions' => self::PER_PAGE_OPTIONS,
            'departments' => $departments,
            'summaryTotals' => [
                'allocated' => (float) $summaryRows->sum('allocated_budget'),
                'utilized' => (float) $summaryRows->sum('utilized_budget'),
                'reserved' => (float) $summaryRows->sum('reserved_budget'),
                'available' => (float) $summaryRows->sum('available_budget'),
            ],
        ]);
    }

    private function baseQuery(): Builder
    {
        return Department::query()
            ->active()
            ->leftJoin('department_budgets as db', function ($join): void {
                $join
                    ->on('db.department_id', '=', 'departments.id')
                    ->where('db.fiscal_year', '=', $this->fiscalYear);
            })
            ->select([
                'departments.id',
                'departments.name',
                'departments.code',
            ])
            ->selectRaw('COALESCE(db.allocated_budget, 0) as allocated_budget')
            ->selectRaw('COALESCE(db.utilized_budget, 0) as utilized_budget')
            ->selectRaw('COALESCE(db.reserved_budget, 0) as reserved_budget')
            ->selectRaw('(COALESCE(db.allocated_budget, 0) - COALESCE(db.utilized_budget, 0) - COALESCE(db.reserved_budget, 0)) as available_budget')
            ->selectRaw('CASE WHEN COALESCE(db.allocated_budget, 0) <= 0 THEN 0 ELSE (COALESCE(db.utilized_budget, 0) / COALESCE(db.allocated_budget, 0)) * 100 END as utilization_percentage');
    }

    private function applySearch(Builder $query): void
    {
        if ($this->search === '') {
            return;
        }

        $term = trim($this->search);

        $query->where(function (Builder $builder) use ($term): void {
            $builder
                ->where('departments.name', 'like', "%{$term}%")
                ->orWhere('departments.code', 'like', "%{$term}%");
        });
    }

    private function applySorting(Builder $query): void
    {
        $map = [
            'department' => 'departments.name',
            'code' => 'departments.code',
            'allocated' => 'allocated_budget',
            'utilized' => 'utilized_budget',
            'reserved' => 'reserved_budget',
            'available' => 'available_budget',
            'utilization' => 'utilization_percentage',
        ];

        $sortColumn = $map[$this->sortField] ?? 'departments.name';
        $query->orderBy($sortColumn, $this->sortDirection);

        if ($this->sortField !== 'department') {
            $query->orderBy('departments.name');
        }
    }

    private function normalizeSort(): void
    {
        if (! in_array($this->sortField, self::SORTABLE_FIELDS, true)) {
            $this->sortField = 'department';
        }

        if (! in_array($this->sortDirection, ['asc', 'desc'], true)) {
            $this->sortDirection = 'asc';
        }
    }

    private function normalizePerPage(): void
    {
        if (! in_array($this->perPage, self::PER_PAGE_OPTIONS, true)) {
            $this->perPage = 20;
        }
    }

    /**
     * @param  list<string>  $columns
     * @return list<string>
     */
    private function sanitizeVisibleColumns(array $columns): array
    {
        $filtered = array_values(array_filter(
            $columns,
            fn (string $column): bool => in_array($column, self::TOGGLEABLE_COLUMNS, true)
        ));

        if ($filtered === []) {
            return self::DEFAULT_VISIBLE_COLUMNS;
        }

        return $filtered;
    }
}
