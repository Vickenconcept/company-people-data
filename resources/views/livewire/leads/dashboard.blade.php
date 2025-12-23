<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading>Lead Generation Dashboard</flux:heading>
            <flux:subheading>Manage your lead generation requests and track results</flux:subheading>
        </div>
        <div class="flex gap-2">
            <a 
                href="{{ route('leads.import') }}" 
                wire:navigate
                class="px-4 py-2 rounded-lg bg-green-600 text-white font-semibold hover:bg-green-700 border-2 border-green-600 inline-block"
                style="color: #ffffff !important;"
            >
                Import Leads
            </a>
            <a 
                href="{{ route('leads.email-templates') }}" 
                wire:navigate
                class="px-4 py-2 rounded-lg bg-purple-600 text-white font-semibold hover:bg-purple-700 border-2 border-purple-600 inline-block"
                style="color: #ffffff !important;"
            >
                Email Templates
            </a>
            <a 
                href="{{ route('leads.create') }}" 
                wire:navigate
                class="px-6 py-2 rounded-lg bg-orange-500 text-white font-semibold hover:bg-orange-600 border-2 border-orange-500 inline-block"
                style="color: #ffffff !important;"
            >
                Create New Lead Request
            </a>
        </div>
    </div>

    @if(session()->has('message'))
        <div class="rounded-lg bg-green-50 border border-green-200 p-4 text-green-800">
            {{ session('message') }}
        </div>
    @endif

    @if(session()->has('error'))
        <div class="rounded-lg bg-red-50 border border-red-200 p-4 text-red-800">
            {{ session('error') }}
        </div>
    @endif

    <!-- Stats Cards -->
    <div class="grid gap-4 md:grid-cols-4 lg:grid-cols-6">
        <div class="rounded-lg border-2 border-gray-300 bg-white p-6 shadow-sm">
            <div class="space-y-1">
                <div class="text-sm font-medium text-gray-600">Total Requests</div>
                <div class="text-2xl font-bold text-gray-900">{{ $stats['total'] }}</div>
            </div>
        </div>
        <div class="rounded-lg border-2 border-gray-300 bg-white p-6 shadow-sm">
            <div class="space-y-1">
                <div class="text-sm font-medium text-gray-600">Completed</div>
                <div class="text-2xl font-bold text-green-700">{{ $stats['completed'] }}</div>
            </div>
        </div>
        <div class="rounded-lg border-2 border-gray-300 bg-white p-6 shadow-sm">
            <div class="space-y-1">
                <div class="text-sm font-medium text-gray-600">Processing</div>
                <div class="text-2xl font-bold text-blue-700">{{ $stats['processing'] }}</div>
            </div>
        </div>
        <div class="rounded-lg border-2 border-gray-300 bg-white p-6 shadow-sm">
            <div class="space-y-1">
                <div class="text-sm font-medium text-gray-600">Companies Found</div>
                <div class="text-2xl font-bold text-purple-700">{{ $stats['total_companies'] }}</div>
            </div>
        </div>
        <div class="rounded-lg border-2 border-gray-300 bg-white p-6 shadow-sm">
            <div class="space-y-1">
                <div class="text-sm font-medium text-gray-600">Contacts Found</div>
                <div class="text-2xl font-bold text-indigo-700">{{ $stats['total_contacts'] }}</div>
            </div>
        </div>
        <div class="rounded-lg border-2 border-gray-300 bg-white p-6 shadow-sm">
            <div class="space-y-1">
                <div class="text-sm font-medium text-gray-600">Conversion Rate</div>
                <div class="text-2xl font-bold text-orange-500">{{ $stats['conversion_rate'] ?? 0 }}%</div>
                <div class="text-xs text-gray-500">{{ $stats['conversion']['converted'] ?? 0 }} / {{ $stats['conversion']['contacted'] ?? 0 }}</div>
            </div>
        </div>
    </div>

    <!-- Conversion Analytics -->
    <div class="grid gap-4 md:grid-cols-5">
        <div class="rounded-lg border-2 border-gray-300 bg-white p-4 shadow-sm">
            <div class="text-xs font-medium text-gray-600">Total Leads</div>
            <div class="text-xl font-bold text-gray-900">{{ $stats['conversion']['total'] ?? 0 }}</div>
        </div>
        <div class="rounded-lg border-2 border-gray-300 bg-white p-4 shadow-sm">
            <div class="text-xs font-medium text-gray-600">Contacted</div>
            <div class="text-xl font-bold text-blue-700">{{ $stats['conversion']['contacted'] ?? 0 }}</div>
        </div>
        <div class="rounded-lg border-2 border-gray-300 bg-white p-4 shadow-sm">
            <div class="text-xs font-medium text-gray-600">Responded</div>
            <div class="text-xl font-bold text-green-700">{{ $stats['conversion']['responded'] ?? 0 }}</div>
        </div>
        <div class="rounded-lg border-2 border-gray-300 bg-white p-4 shadow-sm">
            <div class="text-xs font-medium text-gray-600">Converted</div>
            <div class="text-xl font-bold text-purple-700">{{ $stats['conversion']['converted'] ?? 0 }}</div>
        </div>
        <div class="rounded-lg border-2 border-gray-300 bg-white p-4 shadow-sm">
            <div class="text-xs font-medium text-gray-600">Rejected</div>
            <div class="text-xl font-bold text-red-700">{{ $stats['conversion']['rejected'] ?? 0 }}</div>
        </div>
    </div>

    <!-- Lead Requests Table -->
    <div class="rounded-lg border-2 border-gray-300 bg-white p-6 shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <flux:heading size="lg">Lead Requests</flux:heading>
            <div class="flex gap-2">
                @if(count($selected) > 0)
                    <button 
                        wire:click="bulkDelete"
                        wire:confirm="Are you sure you want to delete {{ count($selected) }} selected lead request(s)? This action cannot be undone."
                        class="px-4 py-2 rounded-lg bg-red-600 text-white font-semibold hover:bg-red-700 border-2 border-red-600"
                        style="color: #ffffff !important;"
                    >
                        Delete Selected ({{ count($selected) }})
                    </button>
                @endif
                <a 
                    href="{{ route('leads.export') }}" 
                    class="px-4 py-2 rounded-lg bg-green-600 text-white font-semibold hover:bg-green-700 border-2 border-green-600"
                    style="color: #ffffff !important;"
                >
                    Export CSV
                </a>
            </div>
        </div>

        <!-- Search and Filters -->
        <div class="mb-4 grid gap-4 md:grid-cols-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                <input 
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Company name or URL..."
                    class="w-full rounded-lg border-2 border-gray-300 px-3 py-2 focus:border-blue-500 focus:outline-none"
                >
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select 
                    wire:model.live="statusFilter"
                    class="w-full rounded-lg border-2 border-gray-300 px-3 py-2 focus:border-blue-500 focus:outline-none"
                >
                    <option value="">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="processing">Processing</option>
                    <option value="completed">Completed</option>
                    <option value="failed">Failed</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                <input 
                    type="date"
                    wire:model.live="dateFrom"
                    class="w-full rounded-lg border-2 border-gray-300 px-3 py-2 focus:border-blue-500 focus:outline-none"
                >
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                <div class="flex gap-2">
                    <input 
                        type="date"
                        wire:model.live="dateTo"
                        class="flex-1 rounded-lg border-2 border-gray-300 px-3 py-2 focus:border-blue-500 focus:outline-none"
                    >
                    @if($search || $statusFilter || $dateFrom || $dateTo)
                        <button 
                            wire:click="clearFilters"
                            class="px-3 py-2 rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200 font-medium"
                        >
                            Clear
                        </button>
                    @endif
                </div>
            </div>
        </div>
        
        @if($leadRequests->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="px-4 py-3 text-left text-sm font-semibold">
                                <input 
                                    type="checkbox" 
                                    wire:model="selectAll"
                                    wire:click="toggleSelectAll"
                                    class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500"
                                />
                            </th>
                            <th class="px-4 py-3 text-left text-sm font-semibold">Company</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold">Status</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold">Target Count</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold">Companies Found</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold">Contacts Found</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold">Created</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($leadRequests as $request)
                            <tr class="border-b border-gray-200 hover:bg-blue-50 transition-colors {{ in_array($request->id, $selected) ? 'bg-blue-100' : '' }}">
                                <td class="px-4 py-3">
                                    <input 
                                        type="checkbox" 
                                        wire:model="selected"
                                        value="{{ $request->id }}"
                                        wire:click="toggleSelect({{ $request->id }})"
                                        class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500"
                                    />
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-medium">{{ $request->reference_company_name }}</div>
                                    @if($request->reference_company_url)
                                        <div class="text-sm text-gray-600">{{ $request->reference_company_url }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @php
                                        $statusColors = [
                                            'pending' => 'bg-yellow-200 text-yellow-900 font-semibold',
                                            'processing' => 'bg-blue-200 text-blue-900 font-semibold',
                                            'completed' => 'bg-green-200 text-green-900 font-semibold',
                                            'failed' => 'bg-red-200 text-red-900 font-semibold',
                                        ];
                                    @endphp
                                    <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold {{ $statusColors[$request->status] ?? 'bg-gray-100 text-gray-800' }}">
                                        {{ ucfirst($request->status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">{{ $request->target_count }}</td>
                                <td class="px-4 py-3">{{ $request->companies_found }}</td>
                                <td class="px-4 py-3">{{ $request->contacts_found }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ $request->created_at->diffForHumans() }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex gap-2">
                                        <a 
                                            href="{{ route('leads.details', $request->id) }}" 
                                            wire:navigate
                                            class="px-3 py-1 rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 text-sm font-medium"
                                        >
                                            View
                                        </a>
                                        <button 
                                            wire:click="delete({{ $request->id }})"
                                            wire:confirm="Are you sure you want to delete this lead request? This action cannot be undone."
                                            class="px-3 py-1 rounded-lg border border-red-300 bg-white text-red-700 hover:bg-red-50 text-sm font-medium"
                                        >
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
                <p class="text-gray-600">No lead requests yet. Create your first one!</p>
                <a 
                    href="{{ route('leads.create') }}" 
                    wire:navigate
                    class="mt-4 px-6 py-2 rounded-lg bg-blue-600 text-white font-semibold hover:bg-blue-700 border-2 border-blue-600 inline-block"
                    style="color: #ffffff !important;"
                >
                    Create Lead Request
                </a>
            </div>
        @endif
    </div>
</div>
