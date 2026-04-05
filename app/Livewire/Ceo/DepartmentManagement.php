<?php

namespace App\Livewire\Ceo;

use App\Models\Department;
use App\Models\DepartmentRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class DepartmentManagement extends Component
{
    use WithPagination;

    private const ALLOWED_TABS = ['departments', 'requests'];

    private const ALLOWED_STATUSES = ['pending', 'approved', 'rejected', ''];

    private const DEPT_SORTABLE = ['name', 'code', 'head_name', 'contact_email', 'is_active'];

    private const REQ_SORTABLE = ['name', 'code', 'requester_email', 'status', 'created_at'];

    private const DEPT_TOGGLE_COLUMNS = ['code', 'head', 'contact', 'status'];

    private const REQ_TOGGLE_COLUMNS = ['code', 'requester', 'status', 'submitted'];

    #[Url]
    public string $tab = 'departments';

    #[Url]
    public string $status = 'pending';

    #[Url(as: 'search')]
    public string $search = '';

    #[Url(as: 'dept_sort')]
    public string $deptSortField = 'name';

    #[Url(as: 'dept_dir')]
    public string $deptSortDirection = 'asc';

    #[Url(as: 'req_sort')]
    public string $reqSortField = 'created_at';

    #[Url(as: 'req_dir')]
    public string $reqSortDirection = 'desc';

    /** @var list<string> */
    public array $deptVisibleColumns = ['code', 'head', 'contact', 'status'];

    /** @var list<string> */
    public array $reqVisibleColumns = ['code', 'requester', 'status', 'submitted'];

    public function mount(): void
    {
        if (! in_array($this->tab, self::ALLOWED_TABS, true)) {
            $this->tab = 'departments';
        }

        if (! in_array($this->status, self::ALLOWED_STATUSES, true)) {
            $this->status = 'pending';
        }

        $this->normalizeDeptSort();

        $this->normalizeReqSort();

        $this->deptVisibleColumns = $this->sanitizeVisibleColumns($this->deptVisibleColumns, self::DEPT_TOGGLE_COLUMNS);

        $this->reqVisibleColumns = $this->sanitizeVisibleColumns($this->reqVisibleColumns, self::REQ_TOGGLE_COLUMNS);
    }

    public function updatingTab(): void
    {
        $this->resetPage();
        $this->search = '';
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function setTab(string $tab): void
    {
        if (! in_array($tab, self::ALLOWED_TABS, true)) {
            return;
        }

        $this->tab = $tab;
        $this->resetPage();
        $this->search = '';
    }

    public function sortByDept(string $field): void
    {
        if (! in_array($field, self::DEPT_SORTABLE, true)) {
            return;
        }

        if ($this->deptSortField === $field) {
            $this->deptSortDirection = $this->deptSortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->deptSortField = $field;
            $this->deptSortDirection = 'asc';
        }

        $this->resetPage();
    }

    public function sortByReq(string $field): void
    {
        if (! in_array($field, self::REQ_SORTABLE, true)) {
            return;
        }

        if ($this->reqSortField === $field) {
            $this->reqSortDirection = $this->reqSortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->reqSortField = $field;
            $this->reqSortDirection = $field === 'created_at' ? 'desc' : 'asc';
        }

        $this->resetPage();
    }

    public function toggleDeptColumn(string $column): void
    {
        $this->toggleColumnInList($column, self::DEPT_TOGGLE_COLUMNS, 'deptVisibleColumns');
    }

    public function toggleReqColumn(string $column): void
    {
        $this->toggleColumnInList($column, self::REQ_TOGGLE_COLUMNS, 'reqVisibleColumns');
    }

    public function isDeptColumnVisible(string $column): bool
    {
        return in_array($column, $this->deptVisibleColumns, true);
    }

    public function isReqColumnVisible(string $column): bool
    {
        return in_array($column, $this->reqVisibleColumns, true);
    }

    public function clearFilters(): void
    {
        $this->resetPage();
        $this->search = '';
        $this->status = 'pending';
    }

    public function render(): View
    {
        $departments = null;
        $departmentRequests = null;
        $pendingCount = DepartmentRequest::where('status', 'pending')->count();

        if ($this->tab === 'departments') {
            $query = Department::query();

            if ($this->search !== '') {
                $term = trim($this->search);
                $query->where(function ($q) use ($term): void {
                    $q->where('name', 'like', "%{$term}%")
                        ->orWhere('code', 'like', "%{$term}%");
                });
            }

            $this->applyDeptSorting($query);

            $departments = $query->paginate(20);
        } else {
            $query = DepartmentRequest::query()->with('reviewer');

            if ($this->status !== '') {
                $query->where('status', $this->status);
            }

            if ($this->search !== '') {
                $term = trim($this->search);
                $query->where(function ($q) use ($term): void {
                    $q->where('name', 'like', "%{$term}%")
                        ->orWhere('code', 'like', "%{$term}%")
                        ->orWhere('requester_email', 'like', "%{$term}%");
                });
            }

            $this->applyReqSorting($query);

            $departmentRequests = $query->paginate(20);
        }

        return view('livewire.ceo.department-management', [
            'departments' => $departments,
            'departmentRequests' => $departmentRequests,
            'pendingCount' => $pendingCount,
            'deptColumnLabels' => [
                'code' => 'Code',
                'head' => 'Head',
                'contact' => 'Contact',
                'status' => 'Status',
            ],
            'reqColumnLabels' => [
                'code' => 'Code',
                'requester' => 'Requester',
                'status' => 'Status',
                'submitted' => 'Submitted',
            ],
        ]);
    }

    private function normalizeDeptSort(): void
    {
        if (! in_array($this->deptSortField, self::DEPT_SORTABLE, true)) {
            $this->deptSortField = 'name';
        }

        if (! in_array($this->deptSortDirection, ['asc', 'desc'], true)) {
            $this->deptSortDirection = 'asc';
        }
    }

    private function normalizeReqSort(): void
    {
        if (! in_array($this->reqSortField, self::REQ_SORTABLE, true)) {
            $this->reqSortField = 'created_at';
        }

        if (! in_array($this->reqSortDirection, ['asc', 'desc'], true)) {
            $this->reqSortDirection = 'desc';
        }
    }

    /**
     * @param  list<string>  $columns
     * @param  list<string>  $allowed
     * @return list<string>
     */
    private function sanitizeVisibleColumns(array $columns, array $allowed): array
    {
        $filtered = array_values(array_filter(
            $columns,
            fn (string $c): bool => in_array($c, $allowed, true)
        ));

        if ($filtered === []) {
            return $allowed;
        }

        return $filtered;
    }

    /**
     * @param  list<string>  $allowedKeys
     */
    private function toggleColumnInList(string $column, array $allowedKeys, string $property): void
    {
        if (! in_array($column, $allowedKeys, true)) {
            return;
        }

        $list = $this->{$property};

        if (in_array($column, $list, true)) {
            $next = array_values(array_filter(
                $list,
                fn (string $visible): bool => $visible !== $column
            ));

            if ($next === []) {
                return;
            }

            $this->{$property} = $next;

            return;
        }

        $list[] = $column;
        $this->{$property} = array_values(array_unique($list));
    }

    private function applyDeptSorting(Builder $query): void
    {
        $field = $this->deptSortField;
        $dir = $this->deptSortDirection;

        $query->orderBy($field, $dir);

        if ($field !== 'name') {
            $query->orderBy('name');
        }
    }

    private function applyReqSorting(Builder $query): void
    {
        $field = $this->reqSortField;
        $dir = $this->reqSortDirection;

        if ($field === 'code') {
            $query->orderByRaw('UPPER(code) '.$dir);
        } else {
            $query->orderBy($field, $dir);
        }

        if ($field !== 'created_at') {
            $query->orderByDesc('created_at');
        }
    }
}
