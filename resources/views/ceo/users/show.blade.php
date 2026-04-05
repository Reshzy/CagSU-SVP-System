<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('ceo.users.index') }}"
                class="inline-flex items-center justify-center h-8 w-8 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors shadow-sm">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white leading-tight">Review User</h2>
                <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">Verify the user's information and submitted documents before deciding.</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Flash --}}
            @if(session('status'))
                <div class="flex items-center gap-3 rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 px-5 py-4 text-sm text-green-800 dark:text-green-300">
                    <svg class="h-5 w-5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    {{ session('status') }}
                </div>
            @endif

            {{-- Top status banner for already-decided users --}}
            @if($user->approval_status === 'approved')
                <div class="flex items-center gap-3 rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 px-5 py-4">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/40">
                        <svg class="h-5 w-5 text-green-700 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div>
                        <div class="text-sm font-semibold text-green-800 dark:text-green-300">User Approved</div>
                        @if($user->approved_at)
                            <div class="text-xs text-green-600 dark:text-green-400">Approved {{ $user->approved_at->format('M d, Y g:i A') }}</div>
                        @endif
                    </div>
                </div>
            @elseif($user->approval_status === 'rejected')
                <div class="flex items-center gap-3 rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 px-5 py-4">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/40">
                        <svg class="h-5 w-5 text-red-700 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-12.728 12.728M5.636 5.636l12.728 12.728"/>
                        </svg>
                    </div>
                    <div>
                        <div class="text-sm font-semibold text-red-800 dark:text-red-300">User Deferred / Rejected</div>
                        @if($user->rejected_at)
                            <div class="text-xs text-red-600 dark:text-red-400">Deferred {{ $user->rejected_at->format('M d, Y g:i A') }}</div>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Two-column grid --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                {{-- LEFT: Profile + Details --}}
                <div class="lg:col-span-2 space-y-6">

                    {{-- Profile card --}}
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                        {{-- Card header with avatar --}}
                        <div class="bg-gradient-to-r from-cagsu-maroon to-cagsu-orange px-6 py-5 flex items-center gap-4">
                            <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-white/20 text-white font-bold text-xl select-none ring-2 ring-white/30">
                                {{ strtoupper(mb_substr($user->name, 0, 1)) }}
                            </div>
                            <div class="min-w-0">
                                <div class="text-white font-bold text-lg leading-tight truncate">{{ $user->name }}</div>
                                <div class="text-white/70 text-sm truncate">{{ $user->email }}</div>
                            </div>
                            <div class="ml-auto shrink-0">
                                @php
                                    $badgeMap = [
                                        'pending'  => 'bg-yellow-400/20 text-yellow-200 ring-yellow-300/30',
                                        'approved' => 'bg-green-400/20 text-green-200 ring-green-300/30',
                                        'rejected' => 'bg-red-400/20 text-red-200 ring-red-300/30',
                                    ];
                                    $badgeClass = $badgeMap[$user->approval_status] ?? 'bg-gray-400/20 text-gray-200 ring-gray-300/30';
                                @endphp
                                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold ring-1 {{ $badgeClass }}">
                                    {{ ucfirst($user->approval_status) }}
                                </span>
                            </div>
                        </div>

                        {{-- Detail rows --}}
                        <dl class="divide-y divide-gray-100 dark:divide-gray-700">
                            @php
                                $details = [
                                    ['label' => 'Full Name',    'value' => $user->name],
                                    ['label' => 'Email',        'value' => $user->email],
                                    ['label' => 'Department',   'value' => optional($user->department)->name ?? null],
                                    ['label' => 'Position',     'value' => $user->position?->name ?? null],
                                    ['label' => 'Employee ID',  'value' => $user->employee_id ?? null],
                                    ['label' => 'Phone',        'value' => $user->phone ?? null],
                                    ['label' => 'Registered',   'value' => $user->created_at->format('M d, Y g:i A').' ('.$user->created_at->diffForHumans().')'],
                                ];
                            @endphp
                            @foreach($details as $row)
                                <div class="grid grid-cols-5 gap-4 px-6 py-3.5 text-sm">
                                    <dt class="col-span-2 font-medium text-gray-500 dark:text-gray-400 self-center">{{ $row['label'] }}</dt>
                                    <dd class="col-span-3 text-gray-900 dark:text-white font-medium self-center">
                                        @if($row['value'])
                                            @if($row['label'] === 'Employee ID')
                                                <span class="font-mono bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 px-1.5 py-0.5 rounded text-xs">{{ $row['value'] }}</span>
                                            @else
                                                {{ $row['value'] }}
                                            @endif
                                        @else
                                            <span class="text-gray-400 dark:text-gray-500">—</span>
                                        @endif
                                    </dd>
                                </div>
                            @endforeach
                        </dl>
                    </div>

                    {{-- Submitted Documents / ID Evidence --}}
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200">Submitted Documents & ID Proof</h3>
                                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                    {{ $user->documents->count() }} {{ Str::plural('file', $user->documents->count()) }} submitted
                                </p>
                            </div>
                        </div>

                        <div class="p-6">
                            @if($user->documents->isEmpty())
                                <div class="flex flex-col items-center justify-center gap-3 rounded-lg border-2 border-dashed border-gray-200 dark:border-gray-700 py-10">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-700">
                                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                    </div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">No documents uploaded.</p>
                                </div>
                            @else
                                @php
                                    $imageMimes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif'];
                                    $imageExts  = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
                                @endphp

                                {{-- Image previews grid --}}
                                @php
                                    $imageDocs    = $user->documents->filter(fn($d) => in_array(strtolower($d->file_extension ?? ''), $imageExts) || in_array($d->mime_type ?? '', $imageMimes));
                                    $nonImageDocs = $user->documents->reject(fn($d) => in_array(strtolower($d->file_extension ?? ''), $imageExts) || in_array($d->mime_type ?? '', $imageMimes));
                                @endphp

                                @if($imageDocs->isNotEmpty())
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                                        @foreach($imageDocs as $doc)
                                            <div class="group relative rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
                                                {{-- Image preview --}}
                                                <a href="{{ route('files.show', $doc) }}" target="_blank" class="block">
                                                    <div class="aspect-video w-full overflow-hidden bg-gray-100 dark:bg-gray-900">
                                                        <img
                                                            src="{{ route('files.show', $doc) }}"
                                                            alt="{{ $doc->title ?? $doc->file_name }}"
                                                            class="h-full w-full object-contain transition-transform duration-200 group-hover:scale-105"
                                                            loading="lazy"
                                                        />
                                                    </div>
                                                </a>
                                                {{-- Overlay meta --}}
                                                <div class="px-4 py-3 flex items-center justify-between gap-2">
                                                    <div class="min-w-0">
                                                        <div class="text-xs font-semibold text-gray-800 dark:text-gray-200 truncate">{{ $doc->title ?? $doc->file_name }}</div>
                                                        <div class="text-xs text-gray-400 dark:text-gray-500 uppercase">{{ $doc->file_extension }}</div>
                                                    </div>
                                                    <a href="{{ route('files.show', $doc) }}" target="_blank"
                                                        class="shrink-0 inline-flex items-center gap-1 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 px-2.5 py-1 text-xs font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors shadow-sm">
                                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                                        </svg>
                                                        Open
                                                    </a>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                {{-- Non-image files list --}}
                                @if($nonImageDocs->isNotEmpty())
                                    <ul class="divide-y divide-gray-100 dark:divide-gray-700 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                                        @foreach($nonImageDocs as $doc)
                                            <li class="flex items-center justify-between gap-4 px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/40 transition-colors">
                                                <div class="flex items-center gap-3 min-w-0">
                                                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gray-100 dark:bg-gray-700">
                                                        <svg class="h-4 w-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                        </svg>
                                                    </div>
                                                    <div class="min-w-0">
                                                        <div class="text-sm font-medium text-gray-800 dark:text-gray-200 truncate">{{ $doc->title ?? $doc->file_name }}</div>
                                                        <div class="text-xs text-gray-400 dark:text-gray-500 uppercase">{{ $doc->file_extension }}</div>
                                                    </div>
                                                </div>
                                                <a href="{{ route('files.show', $doc) }}" target="_blank"
                                                    class="shrink-0 inline-flex items-center gap-1.5 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors shadow-sm">
                                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                                    </svg>
                                                    View
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>

                {{-- RIGHT: Decision panel + meta --}}
                <div class="space-y-6">

                    {{-- Decision card --}}
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <div class="bg-gradient-to-r from-gray-800 to-gray-700 dark:from-gray-900 dark:to-gray-800 px-6 py-4">
                            <h3 class="text-sm font-semibold text-white">Decision</h3>
                            <p class="mt-0.5 text-xs text-gray-400">Take action on this registration.</p>
                        </div>

                        <div class="p-6 space-y-3">
                            @if($user->approval_status === 'pending')
                                {{-- Approve --}}
                                <form method="POST" action="{{ route('ceo.users.approve', $user) }}">
                                    @csrf
                                    <button type="submit"
                                        class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-green-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors shadow-sm"
                                        onclick="return confirm('Approve {{ addslashes($user->name) }}\'s registration?')">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        Approve Registration
                                    </button>
                                </form>

                                {{-- Defer --}}
                                <form method="POST" action="{{ route('ceo.users.reject', $user) }}">
                                    @csrf
                                    <button type="submit"
                                        class="w-full inline-flex items-center justify-center gap-2 rounded-lg border border-red-300 bg-red-50 dark:bg-red-900/20 dark:border-red-700 px-4 py-2.5 text-sm font-semibold text-red-700 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/30 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors"
                                        onclick="return confirm('Defer / reject {{ addslashes($user->name) }}\'s registration?')">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                        Defer Registration
                                    </button>
                                </form>

                            @elseif($user->approval_status === 'approved')
                                {{-- Re-defer option --}}
                                <form method="POST" action="{{ route('ceo.users.reject', $user) }}">
                                    @csrf
                                    <button type="submit"
                                        class="w-full inline-flex items-center justify-center gap-2 rounded-lg border border-red-300 bg-red-50 dark:bg-red-900/20 dark:border-red-700 px-4 py-2.5 text-sm font-semibold text-red-700 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/30 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors"
                                        onclick="return confirm('This will revoke {{ addslashes($user->name) }}\'s access. Continue?')">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                        Revoke Approval
                                    </button>
                                </form>

                            @elseif($user->approval_status === 'rejected')
                                {{-- Re-approve option --}}
                                <form method="POST" action="{{ route('ceo.users.approve', $user) }}">
                                    @csrf
                                    <button type="submit"
                                        class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-green-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors shadow-sm"
                                        onclick="return confirm('Re-approve {{ addslashes($user->name) }}\'s registration?')">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        Re-approve
                                    </button>
                                </form>
                            @endif

                            <a href="{{ route('ceo.users.index') }}"
                                class="block w-full text-center rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                                Back to List
                            </a>
                        </div>
                    </div>

                    {{-- Account metadata --}}
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                            <h3 class="text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wide">Account Info</h3>
                        </div>
                        <dl class="divide-y divide-gray-100 dark:divide-gray-700 text-xs">
                            <div class="flex items-center justify-between px-6 py-3 gap-2">
                                <dt class="text-gray-500 dark:text-gray-400 font-medium">Account Status</dt>
                                <dd>
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold {{ $user->is_active ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400' }}">
                                        {{ $user->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </dd>
                            </div>
                            <div class="flex items-center justify-between px-6 py-3 gap-2">
                                <dt class="text-gray-500 dark:text-gray-400 font-medium">Documents</dt>
                                <dd class="font-semibold text-gray-800 dark:text-gray-200">{{ $user->documents->count() }}</dd>
                            </div>
                            <div class="flex items-center justify-between px-6 py-3 gap-2">
                                <dt class="text-gray-500 dark:text-gray-400 font-medium">Registered</dt>
                                <dd class="text-gray-700 dark:text-gray-300">{{ $user->created_at->format('M d, Y') }}</dd>
                            </div>
                            @if($user->approved_at)
                                <div class="flex items-center justify-between px-6 py-3 gap-2">
                                    <dt class="text-gray-500 dark:text-gray-400 font-medium">Approved</dt>
                                    <dd class="text-gray-700 dark:text-gray-300">{{ $user->approved_at->format('M d, Y') }}</dd>
                                </div>
                            @endif
                            @if($user->rejected_at)
                                <div class="flex items-center justify-between px-6 py-3 gap-2">
                                    <dt class="text-gray-500 dark:text-gray-400 font-medium">Deferred</dt>
                                    <dd class="text-gray-700 dark:text-gray-300">{{ $user->rejected_at->format('M d, Y') }}</dd>
                                </div>
                            @endif
                        </dl>
                    </div>

                </div>
            </div>

        </div>
    </div>
</x-app-layout>
