<?php

use App\Models\Department;
use Livewire\Component;

new class extends Component
{
    public string $departmentId = '';

    public function mount(?string $initialDepartmentId = null): void
    {
        $this->departmentId = (string) ($initialDepartmentId ?? '');
    }

    public function getDepartmentsProperty()
    {
        return Department::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
    }
};
