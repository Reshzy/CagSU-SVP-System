<?php

namespace App\Livewire\Ceo;

use App\Models\Department;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class UsersTable extends Component
{
    use WithPagination;

    #[Url(as: 'search')]
    public string $search = '';

    #[Url]
    public string $status = '';

    #[Url(as: 'department_id')]
    public string $departmentId = '';

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
            ->with(['department', 'position'])
            ->orderByDesc('created_at');

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

        $users = $usersQuery->paginate(20);

        $departments = Department::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('livewire.ceo.users-table', [
            'departments' => $departments,
            'users' => $users,
        ]);
    }
}
