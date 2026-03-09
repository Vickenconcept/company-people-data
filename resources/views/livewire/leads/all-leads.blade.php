<div class="space-y-6">
    <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
        <div>
            <h2 class="text-lg font-semibold text-slate-900">
                {{ __('All Lead Requests') }}
            </h2>
            <p class="mt-1 text-sm text-slate-500">
                {{ __('Every automated discovery job you have created, with full history and filters.') }}
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a
                href="{{ route('leads.export') }}"
                class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-xs font-medium text-slate-700 shadow-sm hover:border-slate-300 hover:bg-slate-50"
            >
                <flux:icon name="arrow-up-tray" class="size-4" />
                <span>{{ __('Export CSV') }}</span>
            </a>
            <a
                href="{{ route('leads.create') }}"
                wire:navigate
                class="inline-flex items-center gap-2 rounded-full bg-orange-500 px-5 py-2 text-xs font-semibold text-white shadow-md shadow-orange-500/30 hover:bg-orange-600"
            >
                <flux:icon name="plus" class="size-4" />
                <span>{{ __('New Lead Request') }}</span>
            </a>
        </div>
    </div>

    @if(session()->has('message'))
        <div class="rounded-lg bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-800">
            {{ session('message') }}
        </div>
    @endif

    @if(session()->has('error'))
        <div class="rounded-lg bg-rose-50 border border-rose-200 p-4 text-sm text-rose-800">
            {{ session('error') }}
        </div>
    @endif

    <!-- Stat cards -->
    <div class="grid gap-4 md:grid-cols-3 lg:grid-cols-4">
        <div class="rounded-2xl bg-white px-5 py-4 shadow-sm border border-slate-100">
            <p class="text-xs font-medium text-slate-500">{{ __('Total Requests') }}</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">
                {{ $stats['total'] ?? 0 }}
            </p>
            <p class="mt-1 text-xs text-slate-500">
                {{ __('All lead discovery jobs you have created.') }}
            </p>
        </div>
        <div class="rounded-2xl bg-white px-5 py-4 shadow-sm border border-slate-100">
            <p class="text-xs font-medium text-slate-500">{{ __('Completed') }}</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">
                {{ $stats['completed'] ?? 0 }}
            </p>
            <p class="mt-1 text-xs text-slate-500">
                {{ __('Finished discovery runs.') }}
            </p>
        </div>
        <div class="rounded-2xl bg-white px-5 py-4 shadow-sm border border-slate-100">
            <p class="text-xs font-medium text-slate-500">{{ __('Companies Found') }}</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">
                {{ $stats['total_companies'] ?? 0 }}
            </p>
            <p class="mt-1 text-xs text-slate-500">
                {{ __('Unique target accounts across all requests.') }}
            </p>
        </div>
        <div class="rounded-2xl bg-white px-5 py-4 shadow-sm border border-slate-100">
            <p class="text-xs font-medium text-slate-500">{{ __('Contacts Found') }}</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">
                {{ $stats['total_contacts'] ?? 0 }}
            </p>
            <p class="mt-1 text-xs text-slate-500">
                {{ __('People profiles discovered from all runs.') }}
            </p>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
        <!-- Bulk Actions -->
        @if(count($selected) > 0)
            <div class="mb-4 flex items-center justify-between rounded-xl bg-orange-50 border border-orange-200 px-4 py-3">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-8 h-8 rounded-full bg-orange-100">
                        <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <span class="text-sm font-semibold text-orange-900">
                        {{ count($selected) }} {{ count($selected) === 1 ? 'item' : 'items' }} selected
                    </span>
                </div>
                <button 
                    wire:click="bulkDelete"
                    wire:confirm="Are you sure you want to delete {{ count($selected) }} selected lead request(s)? This action cannot be undone."
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-red-600 text-white font-semibold hover:bg-red-700 transition-colors cursor-pointer shadow-sm"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    Delete Selected
                </button>
            </div>
        @endif

        <div class="mb-4 grid gap-4 md:grid-cols-4">
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Search</label>
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="{{ __('Company name or URL...') }}"
                    class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700 placeholder:text-slate-400 focus:border-orange-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-orange-400"
                >
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Status</label>
                <select
                    wire:model.live="statusFilter"
                    class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-400"
                >
                    <option value="">{{ __('All Statuses') }}</option>
                    <option value="pending">{{ __('Pending') }}</option>
                    <option value="processing">{{ __('Processing') }}</option>
                    <option value="completed">{{ __('Completed') }}</option>
                    <option value="failed">{{ __('Failed') }}</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Date From</label>
                <input
                    type="date"
                    wire:model.live="dateFrom"
                    class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-400"
                >
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Date To</label>
                <div class="flex gap-2">
                    <input
                        type="date"
                        wire:model.live="dateTo"
                        class="flex-1 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-400"
                    >
                    @if($search || $statusFilter || $dateFrom || $dateTo)
                        <button
                            type="button"
                            wire:click="clearFilters"
                            class="rounded-full bg-slate-100 px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-200"
                        >
                            {{ __('Clear') }}
                        </button>
                    @endif
                </div>
            </div>
        </div>

        @if($leadRequests->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50/60">
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                <input
                                    type="checkbox"
                                    wire:click="toggleSelectAll"
                                    @checked($selectAll)
                                    class="h-4 w-4 rounded border-slate-300 text-orange-500 focus:ring-orange-500 cursor-pointer"
                                />
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Company') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Status') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Target') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Companies') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Contacts') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Created') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($leadRequests as $request)
                            <tr class="border-b border-slate-100 hover:bg-orange-50/60 transition-colors {{ in_array($request->id, $selected) ? 'bg-orange-50' : '' }}">
                                <td class="px-4 py-3">
                                    <input
                                        type="checkbox"
                                        wire:click="toggleSelect({{ $request->id }})"
                                        @checked(in_array($request->id, $selected))
                                        class="h-4 w-4 rounded border-slate-300 text-orange-500 focus:ring-orange-500 cursor-pointer"
                                    />
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-slate-900">{{ $request->reference_company_name }}</div>
                                    @if($request->reference_company_url)
                                        <div class="text-xs text-slate-500">{{ $request->reference_company_url }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @php
                                        $statusColors = [
                                            'pending' => 'bg-amber-50 text-amber-700 ring-1 ring-amber-100',
                                            'processing' => 'bg-sky-50 text-sky-700 ring-1 ring-sky-100',
                                            'completed' => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100',
                                            'failed' => 'bg-rose-50 text-rose-700 ring-1 ring-rose-100',
                                        ];
                                    @endphp
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $statusColors[$request->status] ?? 'bg-slate-100 text-slate-800' }}">
                                        {{ ucfirst($request->status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-800">{{ $request->target_count }}</td>
                                <td class="px-4 py-3 text-sm text-slate-800">{{ $request->companies_found }}</td>
                                <td class="px-4 py-3 text-sm text-slate-800">{{ $request->contacts_found }}</td>
                                <td class="px-4 py-3 text-xs text-slate-500">{{ $request->created_at->diffForHumans() }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex gap-2">
                                        <a
                                            href="{{ route('leads.details', $request->id) }}"
                                            wire:navigate
                                            class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50"
                                        >
                                            {{ __('View') }}
                                        </a>
                                        <button
                                            type="button"
                                            wire:click="delete({{ $request->id }})"
                                            wire:confirm="Are you sure you want to delete this lead request? This action cannot be undone."
                                            class="inline-flex items-center gap-1.5 rounded-full border border-rose-200 bg-white px-3 py-1 text-xs font-medium text-rose-700 hover:bg-rose-50"
                                        >
                                            {{ __('Delete') }}
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
                    {{ __('No lead requests found yet. Create your first one to see it here.') }}
                </p>
                <a
                    href="{{ route('leads.create') }}"
                    wire:navigate
                    class="mt-4 inline-flex items-center gap-2 rounded-full bg-orange-500 px-6 py-2 text-sm font-semibold text-white shadow-md shadow-orange-500/30 hover:bg-orange-600"
                >
                    <flux:icon name="plus" class="size-4" />
                    <span>{{ __('Create Lead Request') }}</span>
                </a>
            </div>
        @endif
    </div>
</div>

