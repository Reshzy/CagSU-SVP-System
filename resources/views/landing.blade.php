<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>CagSU SVP Portal</title>
	@if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
		@vite(['resources/css/app.css', 'resources/js/app.js'])
	@endif
</head>
<body class="min-h-screen bg-[#0a0a0a] text-white">
	<!-- Header -->
	<header class="relative z-20">
		<div class="max-w-7xl mx-auto px-6 py-6 flex items-center justify-between">
			<div class="flex items-center gap-3">
				<x-application-logo class="block h-10 w-auto" />
				<div class="text-sm uppercase tracking-wider text-gray-300">CagSU • Small Value Procurement</div>
			</div>
			<nav class="hidden md:flex items-center gap-3">
				<a href="{{ route('register') }}" class="px-4 py-2 rounded-full border border-white/15 hover:border-white/30 hover:bg-white/5 transition">University Register</a>
				<a href="{{ route('suppliers.register') }}" class="px-4 py-2 rounded-full border border-white/15 hover:border-white/30 hover:bg-white/5 transition">Supplier Register</a>
				<a href="{{ route('suppliers.quotations.submit') }}" class="px-4 py-2 rounded-full border border-white/15 hover:border-white/30 hover:bg-white/5 transition">Submit Quotation</a>
				<a href="{{ route('suppliers.po-status') }}" class="px-4 py-2 rounded-full border border-white/15 hover:border-white/30 hover:bg-white/5 transition">PO Status</a>
				<a href="{{ route('login') }}" class="px-4 py-2 rounded-full bg-cagsu-maroon text-white hover:bg-cagsu-orange transition">Login</a>
			</nav>
		</div>
	</header>

	<!-- Hero -->
	<section class="relative overflow-hidden">
		<div class="absolute inset-0 bg-gradient-to-br from-cagsu-maroon/40 via-cagsu-orange/25 to-transparent"></div>
		<div class="absolute -top-40 -right-20 w-[40rem] h-[40rem] rounded-full bg-cagsu-orange/10 blur-3xl"></div>
		<div class="relative max-w-7xl mx-auto px-6 pt-16 pb-20">
			<div class="grid grid-cols-1 lg:grid-cols-12 gap-10 items-center">
				<div class="lg:col-span-7">
					<h1 class="text-4xl md:text-6xl font-semibold leading-tight">
						<span class="text-white/90">A faster way to</span>
						<span class="block bg-gradient-to-r from-cagsu-orange to-cagsu-maroon bg-clip-text text-transparent">procure, approve, and deliver.</span>
					</h1>
					<p class="mt-5 text-lg text-gray-300 max-w-2xl">End-to-end Small Value Procurement, from request to delivery. Designed for clarity, speed, and accountability.</p>
					<div class="mt-8 flex flex-wrap gap-3">
						<a href="{{ route('register') }}" class="px-5 py-3 rounded-full bg-white text-black hover:bg-gray-100 transition">University Register</a>
						<a href="{{ route('login') }}" class="px-5 py-3 rounded-full border border-white/20 hover:border-white/40 hover:bg-white/5 transition">University Login</a>
						<a href="{{ route('suppliers.register') }}" class="px-5 py-3 rounded-full border border-white/20 hover:border-white/40 hover:bg-white/5 transition">Supplier Register</a>
					</div>
					<div class="mt-6 flex items-center gap-6 text-sm text-gray-400">
						<div>Secure file uploads</div>
						<div>Transparent routing</div>
						<div>Real-time tracking</div>
					</div>
				</div>
				<div class="lg:col-span-5">
					<div class="relative rounded-2xl border border-white/10 bg-white/5 p-6 backdrop-blur">
						<div class="text-sm text-gray-300">Quick Access</div>
						<div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3">
							<a href="{{ route('suppliers.quotations.submit') }}" class="group rounded-xl border border-white/10 p-4 hover:bg-white/5 transition">
								<div class="font-medium">Submit Quotation</div>
								<div class="text-xs text-gray-400 group-hover:text-gray-300">Fast, simple upload flow</div>
							</a>
							<a href="{{ route('suppliers.po-status') }}" class="group rounded-xl border border-white/10 p-4 hover:bg-white/5 transition">
								<div class="font-medium">Check PO Status</div>
								<div class="text-xs text-gray-400 group-hover:text-gray-300">Live delivery status</div>
							</a>
							<a href="{{ route('purchase-requests.create') }}" class="group rounded-xl border border-white/10 p-4 hover:bg-white/5 transition">
								<div class="font-medium">New PR</div>
								<div class="text-xs text-gray-400 group-hover:text-gray-300">Create Purchase Request</div>
							</a>
							<a href="{{ route('purchase-requests.index') }}" class="group rounded-xl border border-white/10 p-4 hover:bg-white/5 transition">
								<div class="font-medium">Track Status</div>
								<div class="text-xs text-gray-400 group-hover:text-gray-300">Follow approvals</div>
							</a>
						</div>
					</div>
				</div>
			</div>
		</div>
	</section>

	<!-- Feature Grid -->
	<section class="relative py-16">
		<div class="max-w-7xl mx-auto px-6">
			<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
				<div class="rounded-2xl border border-white/10 bg-white/5 p-6">
					<div class="text-sm text-gray-400">Visibility</div>
					<div class="mt-2 text-xl font-medium">End‑to‑End Tracking</div>
					<p class="mt-2 text-sm text-gray-400">Know where every request is—no more chasing updates.</p>
				</div>
				<div class="rounded-2xl border border-white/10 bg-white/5 p-6">
					<div class="text-sm text-gray-400">Speed</div>
					<div class="mt-2 text-xl font-medium">Smart Routing</div>
					<p class="mt-2 text-sm text-gray-400">Automatic hand‑offs between Budget, CEO, BAC, and Supply.</p>
				</div>
				<div class="rounded-2xl border border-white/10 bg-white/5 p-6">
					<div class="text-sm text-gray-400">Compliance</div>
					<div class="mt-2 text-xl font-medium">Audit Ready</div>
					<p class="mt-2 text-sm text-gray-400">Every action logged. Files and decisions preserved.</p>
				</div>
			</div>
		</div>
	</section>

	<!-- CTA -->
	<section class="relative py-14">
		<div class="max-w-7xl mx-auto px-6">
			<div class="rounded-2xl border border-white/10 bg-gradient-to-r from-cagsu-maroon/40 to-cagsu-orange/30 p-8 md:p-10">
				<div class="md:flex items-center justify-between gap-8">
					<div>
						<div class="text-sm text-white/70">Ready to start?</div>
						<div class="mt-1 text-2xl md:text-3xl font-semibold">Join the Supplier Portal today.</div>
					</div>
					<div class="mt-6 md:mt-0 flex gap-3">
						<a href="{{ route('suppliers.register') }}" class="px-5 py-3 rounded-full bg-white text-black hover:bg-gray-100 transition">Create Supplier Account</a>
						<a href="{{ route('suppliers.quotations.submit') }}" class="px-5 py-3 rounded-full border border-white/20 hover:border-white/40 hover:bg-white/5 transition">Submit Quotation</a>
					</div>
				</div>
			</div>
		</div>
	</section>

	<!-- Footer -->
	<footer class="border-t border-white/10">
		<div class="max-w-7xl mx-auto px-6 py-8 text-sm text-gray-400 flex flex-col md:flex-row items-center justify-between gap-4">
			<div>© {{ date('Y') }} Cagayan State University • SVP System</div>
			<div class="flex items-center gap-4">
				<a href="{{ route('reports.analytics') }}" class="hover:text-white transition">Analytics</a>
				<a href="{{ route('suppliers.po-status') }}" class="hover:text-white transition">PO Status</a>
				<a href="{{ route('login') }}" class="hover:text-white transition">Login</a>
			</div>
		</div>
	</footer>
</body>
</html>


