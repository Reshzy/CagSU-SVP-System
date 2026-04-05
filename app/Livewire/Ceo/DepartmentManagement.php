<?php

namespace App\Livewire\Ceo;

use App\Models\Department;
use App\Models\DepartmentRequest;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class DepartmentManagement extends Component
{
    use WithPagination;

    private const ALLOWED_TABS = ['departments', 'requests'];

    private const ALLOWED_STATUSES = ['pending', 'approved', 'rejected', ''];

    #[Url]
    public string $tab = 'departments';

    #[Url]
    public string $status = 'pending';

    #[Url(as: 'search')]
    public string $search = '';

    public function mount(): void
    {
        if (! in_array($this->tab, self::ALLOWED_TABS, true)) {
            $this->tab = 'departments';
        }

        if (! in_array($this->status, self::ALLOWED_STATUSES, true)) {
            $this->status = 'pending';
        }
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
            $query = Department::query()->orderBy('name');

            if ($this->search !== '') {
                $term = trim($this->search);
                $query->where(function ($q) use ($term): void {
                    $q->where('name', 'like', "%{$term}%")
                        ->orWhere('code', 'like', "%{$term}%");
                });
            }

            $departments = $query->paginate(20);
        } else {
            $query = DepartmentRequest::query()->with('reviewer')->orderByDesc('created_at');

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

            $departmentRequests = $query->paginate(20);
        }

        return view('livewire.ceo.department-management', [
            'departments' => $departments,
            'departmentRequests' => $departmentRequests,
            'pendingCount' => $pendingCount,
        ]);
    }
}
