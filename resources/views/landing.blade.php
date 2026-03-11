<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>CagSU SVP Portal</title>
	@if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
	@vite(['resources/css/app.css', 'resources/js/app.js'])
	@endif
	<style>
		@media (prefers-reduced-motion: reduce) {
			.svp-marquee-track {
				animation: none !important;
				transform: none !important;
			}
		}

		@keyframes svp-marquee {
			0% {
				transform: translateX(0);
			}

			100% {
				transform: translateX(-50%);
			}
		}

		.svp-marquee-track {
			animation: svp-marquee 22s linear infinite;
			will-change: transform;
		}

		.svp-pattern {
			background-image:
				radial-gradient(1200px 800px at 10% 10%, rgba(255, 176, 51, 0.18), transparent 55%),
				radial-gradient(900px 700px at 70% 0%, rgba(122, 16, 48, 0.22), transparent 52%),
				radial-gradient(1000px 900px at 100% 60%, rgba(255, 176, 51, 0.10), transparent 50%),
				repeating-linear-gradient(135deg, rgba(0, 0, 0, 0.06) 0px, rgba(0, 0, 0, 0.06) 1px, transparent 1px, transparent 12px),
				repeating-linear-gradient(45deg, rgba(0, 0, 0, 0.04) 0px, rgba(0, 0, 0, 0.04) 1px, transparent 1px, transparent 14px);
		}

		.dark .svp-pattern {
			background-image:
				radial-gradient(1200px 800px at 10% 10%, rgba(255, 176, 51, 0.16), transparent 55%),
				radial-gradient(900px 700px at 70% 0%, rgba(122, 16, 48, 0.26), transparent 52%),
				radial-gradient(1000px 900px at 100% 60%, rgba(255, 176, 51, 0.10), transparent 50%),
				repeating-linear-gradient(135deg, rgba(255, 255, 255, 0.05) 0px, rgba(255, 255, 255, 0.05) 1px, transparent 1px, transparent 12px),
				repeating-linear-gradient(45deg, rgba(255, 255, 255, 0.035) 0px, rgba(255, 255, 255, 0.035) 1px, transparent 1px, transparent 14px);
		}
	</style>
</head>

<body class="min-h-screen bg-white text-gray-950 antialiased dark:bg-[#07070a] dark:text-white" x-data="svpLanding()">
	<div class="relative overflow-hidden">
		<div class="pointer-events-none absolute inset-0 svp-pattern opacity-70 dark:opacity-90"></div>
		<div class="pointer-events-none absolute inset-0 bg-gradient-to-b from-white via-white/80 to-white/60 dark:from-[#07070a] dark:via-[#07070a]/70 dark:to-[#07070a]/60"></div>

		<header class="sticky top-0 z-30 border-b border-black/5 bg-white/65 backdrop-blur-xl dark:border-white/10 dark:bg-[#07070a]/50">
			<div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6">
				<a href="{{ url('/') }}" class="group flex items-center gap-3">
					<span class="grid h-10 w-10 place-items-center rounded-2xl border border-black/10 bg-white/70 shadow-sm backdrop-blur dark:border-white/10 dark:bg-white/5">
						<x-application-logo class="block h-8 w-auto" />
					</span>
					<div class="min-w-0">
						<div class="truncate text-sm font-semibold tracking-wide text-gray-950 dark:text-white">CagSU SVP Portal</div>
						<div class="truncate text-[11px] uppercase tracking-[0.22em] text-gray-600 dark:text-white/60">Small Value Procurement</div>
					</div>
				</a>

				<div class="flex items-center gap-2">
					<a href="{{ route('login') }}" class="hidden rounded-full border border-black/10 bg-white/70 px-4 py-2 text-sm font-medium text-gray-950 shadow-sm transition hover:bg-white dark:border-white/15 dark:bg-white/5 dark:text-white dark:hover:bg-white/10 sm:inline-flex">Sign in</a>
					<a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-full bg-gradient-to-r from-cagsu-maroon to-cagsu-orange px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-cagsu-maroon/15 transition hover:brightness-110 focus:outline-none focus-visible:ring-2 focus-visible:ring-cagsu-orange/60">Open portal</a>
				</div>
			</div>
		</header>

		<main>
			<section class="relative">
				<div class="mx-auto grid max-w-7xl grid-cols-1 items-start gap-10 px-4 pb-10 pt-10 sm:px-6 sm:pb-14 sm:pt-14 lg:grid-cols-12 lg:gap-8 lg:pb-20">
					<div class="lg:col-span-6">
						<div class="inline-flex items-center gap-2 rounded-full border border-black/10 bg-white/70 px-3 py-1 text-xs font-medium text-gray-700 shadow-sm backdrop-blur dark:border-white/15 dark:bg-white/5 dark:text-white/70">
							<span class="inline-block h-1.5 w-1.5 rounded-full bg-cagsu-orange"></span>
							<span>Built for clarity, speed, and accountability</span>
						</div>

						<h1 class="mt-5 text-balance text-4xl font-semibold leading-[1.05] text-gray-950 dark:text-white sm:text-6xl">
							<span>Procurement that</span>
							<span class="block bg-gradient-to-r from-cagsu-orange via-cagsu-maroon to-cagsu-orange bg-clip-text text-transparent">moves at the speed of approvals.</span>
						</h1>

						<p class="mt-5 max-w-xl text-pretty text-base leading-relaxed text-gray-700 dark:text-white/70 sm:text-lg">
							A modern Small Value Procurement workflow for Cagayan State University—request, routing, approvals, canvassing, purchase orders, and reporting.
						</p>

						<div class="mt-7 flex flex-col gap-3 sm:flex-row sm:items-center">
							<a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-full bg-gray-950 px-5 py-3 text-sm font-semibold text-white shadow-lg transition hover:bg-black focus:outline-none focus-visible:ring-2 focus-visible:ring-black/30 dark:bg-white dark:text-black dark:hover:bg-gray-100 dark:focus-visible:ring-white/30">University login</a>
							<a href="{{ route('register') }}" class="inline-flex items-center justify-center rounded-full border border-black/10 bg-white/70 px-5 py-3 text-sm font-semibold text-gray-950 shadow-sm transition hover:bg-white focus:outline-none focus-visible:ring-2 focus-visible:ring-cagsu-orange/30 dark:border-white/15 dark:bg-white/5 dark:text-white dark:hover:bg-white/10">Create account</a>
						</div>

						<div class="mt-7 grid grid-cols-3 gap-3 sm:gap-4">
							<div class="rounded-2xl border border-black/10 bg-white/70 p-3 shadow-sm backdrop-blur dark:border-white/10 dark:bg-white/5">
								<div class="text-xs font-medium text-gray-600 dark:text-white/60">Routing</div>
								<div class="mt-1 text-sm font-semibold text-gray-950 dark:text-white">Smart hand-offs</div>
							</div>
							<div class="rounded-2xl border border-black/10 bg-white/70 p-3 shadow-sm backdrop-blur dark:border-white/10 dark:bg-white/5">
								<div class="text-xs font-medium text-gray-600 dark:text-white/60">Tracking</div>
								<div class="mt-1 text-sm font-semibold text-gray-950 dark:text-white">Always visible</div>
							</div>
							<div class="rounded-2xl border border-black/10 bg-white/70 p-3 shadow-sm backdrop-blur dark:border-white/10 dark:bg-white/5">
								<div class="text-xs font-medium text-gray-600 dark:text-white/60">Audit</div>
								<div class="mt-1 text-sm font-semibold text-gray-950 dark:text-white">Log + files</div>
							</div>
						</div>
					</div>

					<div class="lg:col-span-6">
						<div class="relative overflow-hidden rounded-3xl border border-black/10 bg-white/70 shadow-2xl shadow-black/5 backdrop-blur dark:border-white/10 dark:bg-white/5 dark:shadow-black/30" @mouseenter="pause()" @mouseleave="resume()">
							<div class="flex items-center justify-between gap-3 border-b border-black/5 px-4 py-3 dark:border-white/10">
								<div class="flex items-center gap-2 text-xs font-medium text-gray-700 dark:text-white/70">
									<span class="inline-flex h-2 w-2 rounded-full bg-cagsu-orange"></span>
									<span x-text="slides[active].kicker"></span>
								</div>
								<div class="flex items-center gap-2">
									<button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-black/10 bg-white/70 text-gray-950 shadow-sm transition hover:bg-white focus:outline-none focus-visible:ring-2 focus-visible:ring-cagsu-orange/30 dark:border-white/15 dark:bg-white/5 dark:text-white dark:hover:bg-white/10" @click="prev()" aria-label="Previous slide">
										<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4">
											<path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
										</svg>
									</button>
									<button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-black/10 bg-white/70 text-gray-950 shadow-sm transition hover:bg-white focus:outline-none focus-visible:ring-2 focus-visible:ring-cagsu-orange/30 dark:border-white/15 dark:bg-white/5 dark:text-white dark:hover:bg-white/10" @click="next()" aria-label="Next slide">
										<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4">
											<path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
										</svg>
									</button>
								</div>
							</div>

							<div class="relative px-5 py-6 sm:px-7 sm:py-8">
								<div class="absolute inset-0 opacity-80">
									<div class="absolute -left-24 -top-28 h-72 w-72 rounded-full bg-cagsu-orange/15 blur-3xl dark:bg-cagsu-orange/10"></div>
									<div class="absolute -right-24 -bottom-28 h-80 w-80 rounded-full bg-cagsu-maroon/20 blur-3xl dark:bg-cagsu-maroon/25"></div>
								</div>

								<div class="relative grid grid-cols-1 gap-6 sm:gap-8 lg:grid-cols-12 lg:items-center">
									<div class="lg:col-span-6">
										<h2 class="text-balance text-2xl font-semibold leading-tight text-gray-950 dark:text-white sm:text-3xl" x-text="slides[active].title"></h2>
										<p class="mt-3 text-sm leading-relaxed text-gray-700 dark:text-white/70 sm:text-base" x-text="slides[active].body"></p>
										<div class="mt-5 flex flex-wrap gap-2">
											<template x-for="tag in slides[active].tags" :key="tag">
												<span class="inline-flex items-center rounded-full border border-black/10 bg-white/70 px-3 py-1 text-xs font-semibold text-gray-800 shadow-sm dark:border-white/15 dark:bg-white/5 dark:text-white/70" x-text="tag"></span>
											</template>
										</div>
									</div>

									<div class="lg:col-span-6">
										<div class="relative overflow-hidden rounded-2xl border border-black/10 bg-white/70 p-4 shadow-sm dark:border-white/10 dark:bg-white/5 sm:p-6">
											<div class="flex items-center justify-between">
												<div class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-600 dark:text-white/60">Preview</div>
												<div class="flex items-center gap-1.5">
													<div class="h-2 w-2 rounded-full bg-red-400/90"></div>
													<div class="h-2 w-2 rounded-full bg-amber-400/90"></div>
													<div class="h-2 w-2 rounded-full bg-emerald-400/90"></div>
												</div>
											</div>
											<div class="mt-4 grid grid-cols-2 gap-3">
												<div class="rounded-xl border border-black/10 bg-white/70 p-3 dark:border-white/10 dark:bg-white/5">
													<div class="text-[11px] font-medium text-gray-600 dark:text-white/60">Status</div>
													<div class="mt-1 text-sm font-semibold text-gray-950 dark:text-white" x-text="slides[active].metricLabel"></div>
												</div>
												<div class="rounded-xl border border-black/10 bg-white/70 p-3 dark:border-white/10 dark:bg-white/5">
													<div class="text-[11px] font-medium text-gray-600 dark:text-white/60">Throughput</div>
													<div class="mt-1 text-sm font-semibold text-gray-950 dark:text-white" x-text="slides[active].metricValue"></div>
												</div>
												<div class="col-span-2 rounded-xl border border-black/10 bg-gradient-to-r from-cagsu-maroon/20 to-cagsu-orange/15 p-3 dark:border-white/10 dark:from-cagsu-maroon/25 dark:to-cagsu-orange/15">
													<div class="text-[11px] font-medium text-gray-700 dark:text-white/70">Placeholder visual</div>
													<div class="mt-2 h-16 rounded-lg border border-black/10 bg-white/60 dark:border-white/10 dark:bg-white/5"></div>
												</div>
											</div>
										</div>
									</div>
								</div>

								<div class="relative mt-6 flex items-center gap-2">
									<template x-for="(s, idx) in slides" :key="s.kicker">
										<button type="button" class="h-2.5 w-2.5 rounded-full transition" :class="idx === active ? 'bg-cagsu-orange' : 'bg-black/15 hover:bg-black/25 dark:bg-white/15 dark:hover:bg-white/25'" @click="go(idx)" :aria-label="`Go to slide ${idx + 1}`"></button>
									</template>
									<div class="ml-auto text-xs font-medium text-gray-600 dark:text-white/60"><span x-text="active + 1"></span>/<span x-text="slides.length"></span></div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</section>

			<section class="relative pb-8">
				<div class="mx-auto max-w-7xl px-4 sm:px-6">
					<div class="overflow-hidden rounded-3xl border border-black/10 bg-white/70 shadow-sm backdrop-blur dark:border-white/10 dark:bg-white/5">
						<div class="relative flex items-center gap-3 px-4 py-3 sm:px-6">
							<div class="text-xs font-semibold uppercase tracking-[0.24em] text-gray-600 dark:text-white/60">Workflow signals</div>
							<div class="h-px flex-1 bg-black/5 dark:bg-white/10"></div>
							<div class="text-xs font-medium text-gray-600 dark:text-white/60">aesthetic-only</div>
						</div>
						<div class="relative overflow-hidden border-t border-black/5 dark:border-white/10">
							<div class="svp-marquee-track flex w-[200%] items-center gap-3 py-4">
								<div class="flex w-1/2 shrink-0 items-center gap-3 px-4 sm:px-6">
									<div class="rounded-full border border-black/10 bg-white/70 px-4 py-2 text-sm font-semibold text-gray-900 shadow-sm dark:border-white/15 dark:bg-white/5 dark:text-white/80">Request_created</div>
									<div class="rounded-full border border-black/10 bg-white/70 px-4 py-2 text-sm font-semibold text-gray-900 shadow-sm dark:border-white/15 dark:bg-white/5 dark:text-white/80">Budget_checked</div>
									<div class="rounded-full border border-black/10 bg-white/70 px-4 py-2 text-sm font-semibold text-gray-900 shadow-sm dark:border-white/15 dark:bg-white/5 dark:text-white/80">CEO_approved</div>
									<div class="rounded-full border border-black/10 bg-white/70 px-4 py-2 text-sm font-semibold text-gray-900 shadow-sm dark:border-white/15 dark:bg-white/5 dark:text-white/80">BAC_canvass</div>
									<div class="rounded-full border border-black/10 bg-white/70 px-4 py-2 text-sm font-semibold text-gray-900 shadow-sm dark:border-white/15 dark:bg-white/5 dark:text-white/80">PO_generated</div>
									<div class="rounded-full border border-black/10 bg-white/70 px-4 py-2 text-sm font-semibold text-gray-900 shadow-sm dark:border-white/15 dark:bg-white/5 dark:text-white/80">Delivery_tracked</div>
								</div>
								<div class="flex w-1/2 shrink-0 items-center gap-3 px-4 sm:px-6">
									<div class="rounded-full border border-black/10 bg-white/70 px-4 py-2 text-sm font-semibold text-gray-900 shadow-sm dark:border-white/15 dark:bg-white/5 dark:text-white/80">Request_created</div>
									<div class="rounded-full border border-black/10 bg-white/70 px-4 py-2 text-sm font-semibold text-gray-900 shadow-sm dark:border-white/15 dark:bg-white/5 dark:text-white/80">Budget_checked</div>
									<div class="rounded-full border border-black/10 bg-white/70 px-4 py-2 text-sm font-semibold text-gray-900 shadow-sm dark:border-white/15 dark:bg-white/5 dark:text-white/80">CEO_approved</div>
									<div class="rounded-full border border-black/10 bg-white/70 px-4 py-2 text-sm font-semibold text-gray-900 shadow-sm dark:border-white/15 dark:bg-white/5 dark:text-white/80">BAC_canvass</div>
									<div class="rounded-full border border-black/10 bg-white/70 px-4 py-2 text-sm font-semibold text-gray-900 shadow-sm dark:border-white/15 dark:bg-white/5 dark:text-white/80">PO_generated</div>
									<div class="rounded-full border border-black/10 bg-white/70 px-4 py-2 text-sm font-semibold text-gray-900 shadow-sm dark:border-white/15 dark:bg-white/5 dark:text-white/80">Delivery_tracked</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</section>

			<section class="relative pb-16">
				<div class="mx-auto max-w-7xl px-4 sm:px-6">
					<div class="grid grid-cols-1 gap-6 lg:grid-cols-12 lg:gap-8">
						<div class="lg:col-span-5">
							<h3 class="text-balance text-2xl font-semibold text-gray-950 dark:text-white sm:text-3xl">Designed for the real route of approvals.</h3>
							<p class="mt-3 max-w-xl text-sm leading-relaxed text-gray-700 dark:text-white/70 sm:text-base">
								This landing uses animated carousels and patterned backdrops, but the product stays practical: transparent status, clean routing, and audit-ready records.
							</p>
							<div class="mt-6 grid grid-cols-1 gap-3 sm:grid-cols-2">
								<div class="rounded-2xl border border-black/10 bg-white/70 p-4 shadow-sm backdrop-blur dark:border-white/10 dark:bg-white/5">
									<div class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-600 dark:text-white/60">Visibility</div>
									<div class="mt-1 text-base font-semibold text-gray-950 dark:text-white">End-to-end tracking</div>
									<div class="mt-2 text-sm text-gray-700 dark:text-white/70">Know where a request is, and what it needs next.</div>
								</div>
								<div class="rounded-2xl border border-black/10 bg-white/70 p-4 shadow-sm backdrop-blur dark:border-white/10 dark:bg-white/5">
									<div class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-600 dark:text-white/60">Governance</div>
									<div class="mt-1 text-base font-semibold text-gray-950 dark:text-white">Audit-ready trail</div>
									<div class="mt-2 text-sm text-gray-700 dark:text-white/70">Files and decisions are preserved along the way.</div>
								</div>
								<div class="rounded-2xl border border-black/10 bg-white/70 p-4 shadow-sm backdrop-blur dark:border-white/10 dark:bg-white/5 sm:col-span-2">
									<div class="flex flex-wrap items-center justify-between gap-2">
										<div>
											<div class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-600 dark:text-white/60">Quick access</div>
											<div class="mt-1 text-base font-semibold text-gray-950 dark:text-white">Jump straight in</div>
										</div>
										<div class="flex flex-wrap gap-2">
											<a href="{{ route('purchase-requests.create') }}" class="inline-flex items-center justify-center rounded-full bg-gray-950 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-black dark:bg-white dark:text-black dark:hover:bg-gray-100">New PR</a>
											<a href="{{ route('purchase-requests.index') }}" class="inline-flex items-center justify-center rounded-full border border-black/10 bg-white/70 px-4 py-2 text-sm font-semibold text-gray-950 shadow-sm transition hover:bg-white dark:border-white/15 dark:bg-white/5 dark:text-white dark:hover:bg-white/10">Track status</a>
										</div>
									</div>
								</div>
							</div>
						</div>

						<div class="lg:col-span-7">
							<div class="relative overflow-hidden rounded-3xl border border-black/10 bg-white/70 shadow-2xl shadow-black/5 backdrop-blur dark:border-white/10 dark:bg-white/5 dark:shadow-black/30" @mouseenter="pauseCards()" @mouseleave="resumeCards()">
								<div class="flex items-center justify-between gap-3 border-b border-black/5 px-4 py-3 dark:border-white/10">
									<div class="text-xs font-semibold uppercase tracking-[0.24em] text-gray-600 dark:text-white/60">Aesthetic carousel</div>
									<div class="text-xs font-medium text-gray-600 dark:text-white/60">auto-moving cards</div>
								</div>

								<div class="relative px-4 py-5 sm:px-6 sm:py-6">
									<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
										<template x-for="(card, idx) in cards" :key="card.title">
											<div class="rounded-2xl border border-black/10 bg-white/70 p-4 shadow-sm transition dark:border-white/10 dark:bg-white/5" :class="idx === cardActive ? 'ring-2 ring-cagsu-orange/40' : 'hover:bg-white dark:hover:bg-white/10'">
												<div class="flex items-start justify-between gap-3">
													<div>
														<div class="text-sm font-semibold text-gray-950 dark:text-white" x-text="card.title"></div>
														<div class="mt-1 text-sm text-gray-700 dark:text-white/70" x-text="card.body"></div>
													</div>
													<div class="shrink-0 rounded-xl bg-gradient-to-br from-cagsu-maroon/25 to-cagsu-orange/20 p-2 dark:from-cagsu-maroon/30 dark:to-cagsu-orange/20">
														<div class="h-8 w-8 rounded-lg border border-black/10 bg-white/60 dark:border-white/10 dark:bg-white/5"></div>
													</div>
												</div>
												<div class="mt-4 flex items-center justify-between text-xs font-medium text-gray-600 dark:text-white/60">
													<span x-text="card.meta"></span>
													<span class="inline-flex items-center gap-1">
														<span class="h-1.5 w-1.5 rounded-full bg-cagsu-orange"></span>
														<span>scroll-feel</span>
													</span>
												</div>
											</div>
										</template>
									</div>

									<div class="mt-5 flex items-center gap-2">
										<button type="button" class="inline-flex items-center justify-center rounded-full border border-black/10 bg-white/70 px-4 py-2 text-sm font-semibold text-gray-950 shadow-sm transition hover:bg-white focus:outline-none focus-visible:ring-2 focus-visible:ring-cagsu-orange/30 dark:border-white/15 dark:bg-white/5 dark:text-white dark:hover:bg-white/10" @click="cardPrev()">Prev</button>
										<button type="button" class="inline-flex items-center justify-center rounded-full border border-black/10 bg-white/70 px-4 py-2 text-sm font-semibold text-gray-950 shadow-sm transition hover:bg-white focus:outline-none focus-visible:ring-2 focus-visible:ring-cagsu-orange/30 dark:border-white/15 dark:bg-white/5 dark:text-white dark:hover:bg-white/10" @click="cardNext()">Next</button>
										<div class="ml-auto text-xs font-medium text-gray-600 dark:text-white/60"><span x-text="cardActive + 1"></span>/<span x-text="cards.length"></span></div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</section>
		</main>

		<footer class="relative border-t border-black/5 bg-white/50 backdrop-blur dark:border-white/10 dark:bg-white/5">
			<div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-3 px-4 py-8 text-sm text-gray-600 sm:flex-row sm:px-6 dark:text-white/60">
				<div>© {{ date('Y') }} Cagayan State University • SVP System</div>
				<div class="flex flex-wrap items-center gap-4">
					<a href="{{ route('login') }}" class="font-semibold text-gray-900 transition hover:text-black dark:text-white dark:hover:text-white/90">Login</a>
					<a href="{{ route('register') }}" class="font-semibold text-gray-900 transition hover:text-black dark:text-white dark:hover:text-white/90">Register</a>
				</div>
			</div>
		</footer>
	</div>

	<script>
		function svpLanding() {
			const shouldReduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

			return {
				active: 0,
				timer: null,
				slides: [
					{
						kicker: 'Request intake',
						title: 'Create a Purchase Request in minutes.',
						body: 'A clean starting point that keeps the flow consistent from day one—attachments, details, and routing clarity.',
						tags: ['PR', 'Attachments', 'Validation'],
						metricLabel: 'New PRs',
						metricValue: 'Placeholder +0',
					},
					{
						kicker: 'Approvals and routing',
						title: 'Move through Budget, CEO, and BAC with confidence.',
						body: 'Every step is visible, with a consistent experience for reviewers and requesters.',
						tags: ['Budget', 'CEO', 'BAC'],
						metricLabel: 'On-track',
						metricValue: 'Placeholder 98%',
					},
					{
						kicker: 'Supply and delivery',
						title: 'Generate POs and keep delivery status visible.',
						body: 'Turn decisions into purchase orders, then keep progress transparent to reduce follow-ups.',
						tags: ['PO', 'Tracking', 'Records'],
						metricLabel: 'POs',
						metricValue: 'Placeholder +0',
					},
					{
						kicker: 'Reports',
						title: 'Make reporting feel instant.',
						body: 'Structured data and consistent routing makes analytics and summaries easier to trust.',
						tags: ['Analytics', 'Exports', 'History'],
						metricLabel: 'Reports',
						metricValue: 'Placeholder ready',
					},
				],
				cards: [
					{ title: 'Less chasing.', body: 'Statuses are surfaced where people actually look.', meta: 'visibility' },
					{ title: 'Clear hand-offs.', body: 'Budget → CEO → BAC → Supply, without guesswork.', meta: 'routing' },
					{ title: 'Audit-ready by default.', body: 'Files + decisions preserved with the flow.', meta: 'compliance' },
					{ title: 'Mobile-friendly.', body: 'Touch controls, balanced type, and no sideways scroll.', meta: 'responsive' },
				],
				cardActive: 0,
				cardTimer: null,
				init() {
					if (shouldReduceMotion) {
						return;
					}

					this.resume();
					this.resumeCards();
				},
				go(index) {
					this.active = index;
				},
				next() {
					this.active = (this.active + 1) % this.slides.length;
				},
				prev() {
					this.active = (this.active - 1 + this.slides.length) % this.slides.length;
				},
				pause() {
					if (this.timer) {
						clearInterval(this.timer);
						this.timer = null;
					}
				},
				resume() {
					if (shouldReduceMotion) {
						return;
					}

					this.pause();
					this.timer = setInterval(() => this.next(), 6500);
				},
				cardNext() {
					this.cardActive = (this.cardActive + 1) % this.cards.length;
				},
				cardPrev() {
					this.cardActive = (this.cardActive - 1 + this.cards.length) % this.cards.length;
				},
				pauseCards() {
					if (this.cardTimer) {
						clearInterval(this.cardTimer);
						this.cardTimer = null;
					}
				},
				resumeCards() {
					if (shouldReduceMotion) {
						return;
					}

					this.pauseCards();
					this.cardTimer = setInterval(() => this.cardNext(), 4800);
				},
			};
		}
	</script>
</body>

</html>