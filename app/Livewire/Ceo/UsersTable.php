<?php

namespace App\Livewire\Ceo;

use App\Models\Department;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class UsersTable extends Component
{
    use WithPagination;

    private const SORTABLE_FIELDS = [
        'name',
        'department',
        'employee_id',
        'approval_status',
        'created_at',
    ];

    private const DEFAULT_VISIBLE_COLUMNS = [
        'department',
        'employee_id',
        'status',
        'registered',
    ];

    #[Url(as: 'search')]
    public string $search = '';

    #[Url]
    public string $status = '';

    #[Url(as: 'department_id')]
    public string $departmentId = '';

    #[Url(as: 'sort')]
    public string $sortField = 'created_at';

    #[Url(as: 'direction')]
    public string $sortDirection = 'desc';

    public array $visibleColumns = self::DEFAULT_VISIBLE_COLUMNS;

    public function mount(): void
    {
        if (! in_array($this->sortField, self::SORTABLE_FIELDS, true)) {
            $this->sortField = 'created_at';
        }

        if (! in_array($this->sortDirection, ['asc', 'desc'], true)) {
            $this->sortDirection = 'desc';
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function updatingDepartmentId(): void
    {
        $this->resetPage();
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
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    public function toggleColumn(string $column): void
    {
        if (! in_array($column, self::DEFAULT_VISIBLE_COLUMNS, true)) {
            return;
        }

        if (in_array($column, $this->visibleColumns, true)) {
            $this->visibleColumns = array_values(array_filter(
                $this->visibleColumns,
                fn (string $visibleColumn): bool => $visibleColumn !== $column
            ));

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
        $this->resetPage();
        $this->search = '';
        $this->status = '';
        $this->departmentId = '';
    }

    public function render(): View
    {
        $usersQuery = User::query()
            ->with(['department', 'position']);

        if ($this->status !== '') {
            $usersQuery->where('approval_status', $this->status);
        }

        if ($this->departmentId !== '') {
            $usersQuery->where('department_id', $this->departmentId);
        }

        if ($this->search !== '') {
            $searchTerm = trim($this->search);

            $usersQuery->where(function ($query) use ($searchTerm): void {
                $query
                    ->where('name', 'like', "%{$searchTerm}%")
                    ->orWhere('email', 'like', "%{$searchTerm}%");
            });
        }

        $this->applySorting($usersQuery);

        $users = $usersQuery->paginate(20);

        $departments = Department::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('livewire.ceo.users-table', [
            'columnLabels' => [
                'department' => 'Department',
                'employee_id' => 'Employee ID',
                'status' => 'Status',
                'registered' => 'Registered',
            ],
            'departments' => $departments,
            'users' => $users,
        ]);
    }

    private function applySorting(Builder $usersQuery): void
    {
        if ($this->sortField === 'department') {
            $usersQuery
                ->leftJoin('departments', 'departments.id', '=', 'users.department_id')
                ->select('users.*')
                ->orderBy('departments.name', $this->sortDirection)
                ->orderBy('users.name');

            return;
        }

        $usersQuery->orderBy($this->sortField, $this->sortDirection);

        if ($this->sortField !== 'created_at') {
            $usersQuery->orderByDesc('created_at');
        }
    }
}
