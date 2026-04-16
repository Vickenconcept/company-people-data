<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
        <div>
            <h2 class="text-xl font-semibold text-slate-900">{{ __('Lead Performance Overview') }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ __('Track your lead discovery pipeline in one clean view.') }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('leads.import') }}" wire:navigate
                class="inline-flex items-center gap-2 rounded-2xl border border-violet-100 bg-white px-4 py-2 text-sm font-medium !text-slate-700 shadow-sm hover:bg-violet-50/40">
                <flux:icon name="arrow-down-tray" class="size-4" />
                <span>{{ __('Import') }}</span>
            </a>
            <a href="{{ route('leads.email-templates') }}" wire:navigate
                class="inline-flex items-center gap-2 rounded-2xl border border-violet-100 bg-white px-4 py-2 text-sm font-medium !text-slate-700 shadow-sm hover:bg-violet-50/40">
                <flux:icon name="envelope" class="size-4" />
                <span>{{ __('Templates') }}</span>
            </a>
            <a href="{{ route('leads.create') }}" wire:navigate
                class="inline-flex items-center gap-2 rounded-2xl bg-gradient-to-r from-[#8f47f2] to-[#5d30dc] px-4 py-2 text-sm font-semibold text-white shadow-md shadow-violet-300/60 hover:opacity-95">
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

    <!-- Top KPI cards -->
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-3xl border border-violet-100/80 bg-white px-5 py-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">{{ __('Total') }}</p>
            <p class="mt-2 text-4xl font-bold text-[#ff8d47]">{{ $stats['total'] }}</p>
        </div>
        <div class="rounded-3xl border border-violet-100/80 bg-white px-5 py-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">{{ __('Contacts') }}</p>
            <p class="mt-2 text-4xl font-bold text-[#9c4cff]">{{ $stats['total_contacts'] }}</p>
        </div>
        <div class="rounded-3xl border border-violet-100/80 bg-white px-5 py-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">{{ __('Conversion') }}</p>
            <p class="mt-2 text-4xl font-bold text-[#ff3ca4]">{{ $stats['conversion_rate'] ?? 0 }}%</p>
        </div>
        <div class="rounded-3xl border border-violet-100/80 bg-white px-5 py-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">{{ __('In Progress') }}</p>
            <p class="mt-2 text-4xl font-bold text-[#5d30dc]">{{ $stats['processing'] }}</p>
        </div>
    </div>

    <!-- Visual dashboard strip -->
    <div class="grid gap-4 xl:grid-cols-3">
        <div class="rounded-3xl border border-violet-100/80 bg-white p-5 shadow-sm xl:col-span-2">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-slate-500">{{ __('Income Graphic') }}</h3>
                <span class="rounded-full bg-violet-50 px-3 py-1 text-xs font-semibold text-violet-600">{{ __('Live') }}</span>
            </div>
            <div class="relative h-52 overflow-hidden rounded-2xl bg-gradient-to-b from-slate-50 to-white px-4 py-3">
                <div class="absolute inset-x-4 bottom-3 top-3 flex flex-col justify-between text-[10px] text-slate-300">
                    <div class="h-px w-full bg-slate-200"></div>
                    <div class="h-px w-full bg-slate-200"></div>
                    <div class="h-px w-full bg-slate-200"></div>
                    <div class="h-px w-full bg-slate-200"></div>
                </div>
                <div class="absolute bottom-3 left-4 right-4 h-36 rounded-2xl bg-gradient-to-r from-[#ff8d47]/80 via-[#ff5c8d]/80 to-[#f5ab42]/80 [clip-path:polygon(0%_75%,12%_58%,26%_50%,38%_55%,52%_20%,64%_35%,77%_85%,90%_60%,100%_45%,100%_100%,0_100%)]"></div>
                <div class="absolute bottom-1 left-4 right-4 flex justify-between text-[10px] font-medium text-slate-400">
                    <span>Jan</span><span>Feb</span><span>Mar</span><span>Apr</span><span>May</span><span>Jun</span><span>Jul</span>
                </div>
            </div>
        </div>
        <div class="space-y-4">
            <div class="rounded-3xl border border-violet-100/80 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-semibold text-slate-500">{{ __('Most View Item') }}</h3>
                <div class="mt-4 space-y-3 text-xs">
                    <div class="flex items-center justify-between">
                        <span class="font-medium text-slate-500">#{{ $stats['total_companies'] }}</span>
                        <span class="rounded-full bg-rose-50 px-2 py-0.5 font-semibold text-rose-600">{{ __('View') }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="font-medium text-slate-500">#{{ $stats['total_contacts'] }}</span>
                        <span class="rounded-full bg-rose-50 px-2 py-0.5 font-semibold text-rose-600">{{ __('View') }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="font-medium text-slate-500">#{{ $stats['completed'] }}</span>
                        <span class="rounded-full bg-rose-50 px-2 py-0.5 font-semibold text-rose-600">{{ __('View') }}</span>
                    </div>
                </div>
            </div>
            <div class="rounded-3xl border border-violet-100/80 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-semibold text-slate-500">{{ __('Growth') }}</h3>
                <div class="mt-4 flex h-16 items-end gap-1">
                    @for ($i = 0; $i < 20; $i++)
                        <div class="w-2 rounded-t {{ $i % 3 === 0 ? 'bg-[#5d30dc]' : ($i % 2 === 0 ? 'bg-[#ff3ca4]' : 'bg-[#6ea0ff]') }}"
                            style="height: {{ 20 + (($i * 13) % 40) }}px;"></div>
                    @endfor
                </div>
            </div>
        </div>
    </div>

    <!-- Lead Requests Table + right widgets -->
    <div class="grid gap-4 lg:grid-cols-4">
        <div class="lg:col-span-3">
            <div class="rounded-3xl border border-violet-100/80 bg-white p-6 shadow-sm">
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
                            class="inline-flex items-center gap-2 rounded-2xl border border-violet-100 bg-white px-4 py-2 text-xs font-medium text-slate-700 shadow-sm hover:bg-violet-50/40">
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
                            class="w-full rounded-2xl border border-violet-100 bg-violet-50/30 px-3 py-2 text-sm text-slate-700 placeholder:text-slate-400 focus:border-violet-400 focus:bg-white focus:outline-none focus:ring-1 focus:ring-violet-300">
                    </div>
                    <div>
                        <label
                            class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Status</label>
                        <select wire:model.live="statusFilter"
                            class="w-full rounded-2xl border border-violet-100 bg-white px-3 py-2 text-sm text-slate-700 focus:border-violet-400 focus:outline-none focus:ring-1 focus:ring-violet-300">
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
                            class="w-full rounded-2xl border border-violet-100 bg-white px-3 py-2 text-sm text-slate-700 focus:border-violet-400 focus:outline-none focus:ring-1 focus:ring-violet-300">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Date
                            To</label>
                        <div class="flex gap-2">
                            <input type="date" wire:model.live="dateTo"
                                class="flex-1 rounded-2xl border border-violet-100 bg-white px-3 py-2 text-sm text-slate-700 focus:border-violet-400 focus:outline-none focus:ring-1 focus:ring-violet-300">
                            @if ($search || $statusFilter || $dateFrom || $dateTo)
                                <button wire:click="clearFilters"
                                    class="rounded-2xl bg-slate-100 px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-200">
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
                                <tr class="border-b border-slate-200 bg-slate-50/80">
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        <input type="checkbox" wire:click="toggleSelectAll"
                                            @checked($selectAll)
                                            class="w-4 h-4 text-violet-500 bg-gray-100 border-slate-300 rounded focus:ring-violet-500 cursor-pointer" />
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
                                        class="border-b border-slate-100 hover:bg-violet-50/60 transition-colors {{ in_array($request->id, $selected) ? 'bg-violet-50/70' : '' }}">
                                        <td class="px-4 py-3">
                                            <input type="checkbox"
                                                wire:click="toggleSelect({{ $request->id }})"
                                                @checked(in_array($request->id, $selected))
                                                class="w-4 h-4 text-violet-500 bg-gray-100 border-slate-300 rounded focus:ring-violet-500 cursor-pointer" />
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
                                                    class="inline-flex items-center gap-1.5 rounded-2xl border border-slate-200 bg-white px-3 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50">
                                                    View
                                                </a>
                                                <button wire:click="delete({{ $request->id }})"
                                                    wire:confirm="Are you sure you want to delete this lead request? This action cannot be undone."
                                                    class="inline-flex items-center gap-1.5 rounded-2xl border border-rose-200 bg-white px-3 py-1 text-xs font-medium text-rose-700 hover:bg-rose-50">
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
                            class="mt-4 inline-flex items-center gap-2 rounded-2xl bg-gradient-to-r from-[#8f47f2] to-[#5d30dc] px-6 py-2 text-sm font-semibold text-white shadow-md shadow-violet-300/60 hover:opacity-95">
                            <flux:icon name="plus" class="size-4" />
                            <span>{{ __('Create Lead Request') }}</span>
                        </a>
                    </div>
                @endif
            </div>

        </div>
        <div class="space-y-4">
            <div class="rounded-3xl bg-gradient-to-br from-violet-50 to-white px-5 py-4 shadow-sm border border-violet-100">
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

            <div class="rounded-3xl bg-gradient-to-br from-indigo-50 to-white px-5 py-4 shadow-sm border border-indigo-100">
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

            <div class="rounded-3xl bg-gradient-to-br from-amber-50 to-white px-5 py-4 shadow-sm border border-amber-100">
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
