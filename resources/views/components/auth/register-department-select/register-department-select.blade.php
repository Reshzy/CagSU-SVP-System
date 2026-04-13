<div>
    <select
        id="department_id"
        name="department_id"
        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-cagsu-maroon focus:ring-cagsu-maroon dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 dark:focus:border-cagsu-yellow dark:focus:ring-cagsu-yellow"
        required
        wire:model.live="departmentId"
        wire:poll.5s
        x-on:change="$dispatch('department-changed', { value: $event.target.value })"
    >
        <option value="">— Select department —</option>
        @foreach($this->departments as $dept)
            <option value="{{ $dept->id }}">{{ $dept->name }}</option>
        @endforeach
    </select>
</div>