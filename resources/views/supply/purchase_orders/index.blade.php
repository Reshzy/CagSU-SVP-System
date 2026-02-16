@section('title', 'Supply - Purchase Orders')

<x-app-layout>
	<x-slot name="header">
		<h2 class="font-semibold text-2xl text-gray-800 leading-tight">{{ __('Purchase Orders') }}</h2>
	</x-slot>

	<div class="py-8">
		<div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
			<livewire:supply.purchase-order-table />
		</div>
	</div>
</x-app-layout>


