@section('title', 'Reports - Analytics')

<x-app-layout>
	<x-slot name="header">
		<h2 class="font-semibold text-2xl text-gray-800 leading-tight">{{ __('Procurement Analytics') }}</h2>
	</x-slot>

	<div class="py-8">
		<div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
			<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
				<div class="p-6 text-gray-900 space-y-8">
					<div>
						<h3 class="text-lg font-semibold mb-2">Monthly PR Counts</h3>
						<canvas id="chartCounts" height="100"></canvas>
					</div>

					<div>
						<h3 class="text-lg font-semibold mb-2">Average Cycle Time (days)</h3>
						<canvas id="chartCycle" height="100"></canvas>
					</div>
				</div>
			</div>
		</div>
	</div>

	<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
	<script>
		const labels = @json($labels->values());
		const counts = @json($counts->values());
		const cycle = @json($cycle->values());

		new Chart(document.getElementById('chartCounts').getContext('2d'), {
			type: 'bar',
			data: {
				labels,
				datasets: [{
					label: 'PRs Created',
					data: counts,
					backgroundColor: 'rgba(128, 0, 0, 0.5)'
				}]
			},
			options: { responsive: true, maintainAspectRatio: false }
		});

		new Chart(document.getElementById('chartCycle').getContext('2d'), {
			type: 'line',
			data: {
				labels,
				datasets: [{
					label: 'Avg Cycle Time (days)',
					data: cycle,
					borderColor: 'rgba(255, 140, 0, 1)',
					backgroundColor: 'rgba(255, 140, 0, 0.2)',
					fill: true
				}]
			},
			options: { responsive: true, maintainAspectRatio: false }
		});
	</script>
</x-app-layout>


