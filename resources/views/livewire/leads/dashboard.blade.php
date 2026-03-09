<div class="space-y-6">
    <!-- Top action bar -->
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <h2 class="text-lg font-semibold text-slate-900">
                {{ __('Lead Performance Overview') }}
            </h2>
            <p class="mt-1 text-sm text-slate-500">
                {{ __('Track your lead discovery, outreach, and conversion in one place.') }}
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('leads.import') }}" wire:navigate
                class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-medium !text-slate-700 shadow-sm hover:border-slate-300 hover:bg-slate-50">
                <flux:icon name="arrow-down-tray" class="size-4" />
                <span>{{ __('Import Leads') }}</span>
            </a>
            <a href="{{ route('leads.email-templates') }}" wire:navigate
                class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-medium !text-slate-700 shadow-sm hover:border-slate-300 hover:bg-slate-50">
                <flux:icon name="envelope" class="size-4" />
                <span>{{ __('Email Templates') }}</span>
            </a>
            <a href="{{ route('leads.create') }}" wire:navigate
                class="inline-flex items-center gap-2 rounded-full bg-orange-500 px-5 py-2 text-sm font-semibold text-white shadow-md shadow-orange-500/30 hover:bg-orange-600">
                <flux:icon name="plus" class="size-4 !text-white" />
                <span class="!text-white">{{ __('New Lead Request') }}</span>
            </a>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="rounded-lg bg-green-50 border border-green-200 p-4 text-green-800">
            {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="rounded-lg bg-red-50 border border-red-200 p-4 text-red-800">
            {{ session('error') }}
        </div>
    @endif

    <!-- Primary metrics row: highlight card + quick stats -->
    <div class="grid gap-4 lg:grid-cols-3">
        <!-- Highlight card -->
        <div
            class=" rounded-2xl bg-gradient-to-r from-orange-500 via-orange-400 to-orange-500 px-6 py-5 text-white shadow-lg">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-orange-100">
                        {{ __('Leads Summary') }}
                    </p>
                    <p class="mt-2 text-3xl font-semibold">
                        {{ $stats['total'] }} <span
                            class="text-base font-normal opacity-90">{{ __('total requests') }}</span>
                    </p>
                    <p class="mt-3 text-sm text-orange-50">
                        {{ __('See how many companies and contacts your automation has discovered so far.') }}
                    </p>
                </div>
                <div class="text-right">
                    <p class="text-xs font-medium text-orange-100">
                        {{ __('Conversion rate') }}
                    </p>
                    <p class="mt-1 text-2xl font-semibold">
                        {{ $stats['conversion_rate'] ?? 0 }}%
                    </p>
                    <p class="mt-1 text-[11px] text-orange-100/90">
                        {{ $stats['conversion']['converted'] ?? 0 }} /
                        {{ $stats['conversion']['contacted'] ?? 0 }} {{ __('converted leads') }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Small stat cards -->
        <div
            class="rounded-2xl bg-gradient-to-br from-emerald-50 to-white px-5 py-4 shadow-sm border border-emerald-100 flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <p class="text-xs font-medium text-emerald-600">{{ __('Completed') }}</p>
                <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-semibold text-emerald-700">
                    {{ __('Done') }}
                </span>
            </div>
            <p class="mt-2 text-2xl font-semibold text-slate-900">
                {{ $stats['completed'] }}
            </p>
            <p class="mt-1 text-xs text-slate-500">
                {{ __('Lead discovery jobs finished') }}
            </p>
        </div>

        <div
            class="rounded-2xl bg-gradient-to-br from-sky-50 to-white px-5 py-4 shadow-sm border border-sky-100 flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <p class="text-xs font-medium text-sky-600">{{ __('In progress') }}</p>
                <span class="rounded-full bg-sky-100 px-2 py-0.5 text-[11px] font-semibold text-sky-700">
                    {{ __('Live') }}
                </span>
            </div>
            <p class="mt-2 text-2xl font-semibold text-slate-900">
                {{ $stats['processing'] }}
            </p>
            <p class="mt-1 text-xs text-slate-500">
                {{ __('Jobs currently running') }}
            </p>
        </div>
    </div>

    <!-- Lead Requests Table with Stats Sidebar -->
    <div class="grid gap-4 lg:grid-cols-4">
        <!-- Table Section (3 columns) -->
        <div class="lg:col-span-3">
            <div class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
                <div class="mb-5 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900">
                            {{ __('Lead Requests') }}
                        </h3>
                        <p class="mt-1 text-xs text-slate-500">
                            {{ __('Every automated discovery job you have created, in one table.') }}
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @if (count($selected) > 0)
                            <button wire:click="bulkDelete"
                                wire:confirm="Are you sure you want to delete {{ count($selected) }} selected lead request(s)? This action cannot be undone."
                                class="px-4 py-2 rounded-lg bg-red-600 text-white font-semibold hover:bg-red-700 border-2 border-red-600"
                                style="color: #ffffff !important;">
                                Delete Selected ({{ count($selected) }})
                            </button>
                        @endif
                        <a href="{{ route('leads.export') }}"
                            class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-xs font-medium text-slate-700 shadow-sm hover:border-slate-300 hover:bg-slate-50">
                            <flux:icon name="arrow-up-tray" class="size-4" />
                            <span>{{ __('Export CSV') }}</span>
                        </a>
                    </div>
                </div>

                <!-- Search and Filters -->
                <div class="mb-4 grid gap-4 md:grid-cols-4">
                    <div>
                        <label
                            class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Search</label>
                        <input type="text" wire:model.live.debounce.300ms="search"
                            placeholder="Company name or URL..."
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700 placeholder:text-slate-400 focus:border-orange-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-orange-400">
                    </div>
                    <div>
                        <label
                            class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Status</label>
                        <select wire:model.live="statusFilter"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-400">
                            <option value="">All Statuses</option>
                            <option value="pending">Pending</option>
                            <option value="processing">Processing</option>
                            <option value="completed">Completed</option>
                            <option value="failed">Failed</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Date
                            From</label>
                        <input type="date" wire:model.live="dateFrom"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-400">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Date
                            To</label>
                        <div class="flex gap-2">
                            <input type="date" wire:model.live="dateTo"
                                class="flex-1 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-400">
                            @if ($search || $statusFilter || $dateFrom || $dateTo)
                                <button wire:click="clearFilters"
                                    class="rounded-full bg-slate-100 px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-200">
                                    Clear
                                </button>
                            @endif
                        </div>
                    </div>
                </div>

                @if ($leadRequests->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-slate-200 bg-slate-50/60">
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        <input type="checkbox" wire:click="toggleSelectAll"
                                            @checked($selectAll)
                                            class="w-4 h-4 text-orange-500 bg-gray-100 border-slate-300 rounded focus:ring-orange-500 cursor-pointer" />
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Company</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Status</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Target</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Companies</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Contacts</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Created</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($leadRequests as $request)
                                    <tr
                                        class="border-b border-slate-100 hover:bg-orange-50/60 transition-colors {{ in_array($request->id, $selected) ? 'bg-orange-50' : '' }}">
                                        <td class="px-4 py-3">
                                            <input type="checkbox"
                                                wire:click="toggleSelect({{ $request->id }})"
                                                @checked(in_array($request->id, $selected))
                                                class="w-4 h-4 text-orange-500 bg-gray-100 border-slate-300 rounded focus:ring-orange-500 cursor-pointer" />
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="font-medium text-slate-900">
                                                {{ $request->reference_company_name }}</div>
                                            @if ($request->reference_company_url)
                                                <div class="text-xs text-slate-500">
                                                    {{ $request->reference_company_url }}</div>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            @php
                                                $statusColors = [
                                                    'pending' => 'bg-amber-50 text-amber-700 ring-1 ring-amber-100',
                                                    'processing' => 'bg-sky-50 text-sky-700 ring-1 ring-sky-100',
                                                    'completed' =>
                                                        'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100',
                                                    'failed' => 'bg-rose-50 text-rose-700 ring-1 ring-rose-100',
                                                ];
                                            @endphp
                                            <span
                                                class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $statusColors[$request->status] ?? 'bg-slate-100 text-slate-800' }}">
                                                {{ ucfirst($request->status) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-slate-800">{{ $request->target_count }}</td>
                                        <td class="px-4 py-3 text-sm text-slate-800">{{ $request->companies_found }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-slate-800">{{ $request->contacts_found }}
                                        </td>
                                        <td class="px-4 py-3 text-xs text-slate-500">
                                            {{ $request->created_at->diffForHumans() }}</td>
                                        <td class="px-4 py-3">
                                            <div class="flex gap-2">
                                                <a href="{{ route('leads.details', $request->id) }}" wire:navigate
                                                    class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50">
                                                    View
                                                </a>
                                                <button wire:click="delete({{ $request->id }})"
                                                    wire:confirm="Are you sure you want to delete this lead request? This action cannot be undone."
                                                    class="inline-flex items-center gap-1.5 rounded-full border border-rose-200 bg-white px-3 py-1 text-xs font-medium text-rose-700 hover:bg-rose-50">
                                                    Delete
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $leadRequests->links() }}
                    </div>
                @else
                    <div class="py-12 text-center">
                        <p class="text-sm text-slate-500">
                            {{ __('No lead requests yet. Start by creating your first one.') }}
                        </p>
                        <a href="{{ route('leads.create') }}" wire:navigate
                            class="mt-4 inline-flex items-center gap-2 rounded-full bg-orange-500 px-6 py-2 text-sm font-semibold text-white shadow-md shadow-orange-500/30 hover:bg-orange-600">
                            <flux:icon name="plus" class="size-4" />
                            <span>{{ __('Create Lead Request') }}</span>
                        </a>
                    </div>
                @endif
            </div>

        </div>
        <!-- Stats Sidebar (1 column) -->
        <div class="space-y-4">
            <!-- Companies discovered card -->
            <div
                class="rounded-2xl bg-gradient-to-br from-violet-50 to-white px-5 py-4 shadow-sm border border-violet-100">
                <div class="flex items-start justify-between mb-2">
                    <p class="text-xs font-semibold text-violet-600 uppercase tracking-wider">
                        {{ __('Companies discovered') }}</p>
                    <div class="flex items-center justify-center w-8 h-8 rounded-full bg-violet-100">
                        <svg class="w-4 h-4 text-violet-600" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                </div>
                <p class="text-3xl font-bold text-slate-900">
                    {{ $stats['total_companies'] }}
                </p>
                <p class="mt-2 text-xs text-slate-500 leading-relaxed">
                    {{ __('Unique target accounts found by the engine') }}
                </p>
            </div>

            <!-- Contacts discovered card -->
            <div
                class="rounded-2xl bg-gradient-to-br from-indigo-50 to-white px-5 py-4 shadow-sm border border-indigo-100">
                <div class="flex items-start justify-between mb-2">
                    <p class="text-xs font-semibold text-indigo-600 uppercase tracking-wider">
                        {{ __('Contacts discovered') }}</p>
                    <div class="flex items-center justify-center w-8 h-8 rounded-full bg-indigo-100">
                        <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                </div>
                <p class="text-3xl font-bold text-slate-900">
                    {{ $stats['total_contacts'] }}
                </p>
                <p class="mt-2 text-xs text-slate-500 leading-relaxed">
                    {{ __('People profiles with reachable emails') }}
                </p>
            </div>

            <!-- Lead funnel card -->
            <div
                class="rounded-2xl bg-gradient-to-br from-amber-50 to-white px-5 py-4 shadow-sm border border-amber-100">
                <div class="flex items-start justify-between mb-3">
                    <p class="text-xs font-semibold text-amber-600 uppercase tracking-wider">
                        {{ __('Lead funnel') }}</p>
                    <div class="flex items-center justify-center w-8 h-8 rounded-full bg-amber-100">
                        <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                    </div>
                </div>
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-slate-600">{{ __('Contacted') }}</span>
                        <span
                            class="text-lg font-bold text-slate-900">{{ $stats['conversion']['contacted'] ?? 0 }}</span>
                    </div>
                    <div class="w-full h-px bg-amber-100"></div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-slate-600">{{ __('Responded') }}</span>
                        <span
                            class="text-lg font-bold text-slate-900">{{ $stats['conversion']['responded'] ?? 0 }}</span>
                    </div>
                    <div class="w-full h-px bg-amber-100"></div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-slate-600">{{ __('Converted') }}</span>
                        <span
                            class="text-lg font-bold text-slate-900">{{ $stats['conversion']['converted'] ?? 0 }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
