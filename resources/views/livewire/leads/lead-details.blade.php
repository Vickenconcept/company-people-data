<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading>{{ $leadRequest->reference_company_name }}</flux:heading>
            <flux:subheading>Lead Request Details</flux:subheading>
        </div>
        <a 
            href="{{ route('leads.dashboard') }}" 
            wire:navigate
            class="px-4 py-2 rounded-lg border-2 border-gray-300 bg-white text-gray-700 font-semibold hover:bg-gray-50"
        >
            Back to Dashboard
        </a>
    </div>

    <!-- Request Info -->
    <div class="rounded-lg border-2 border-gray-300 bg-white p-6 shadow-sm">
        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <div class="text-sm font-medium text-gray-600">Status</div>
                @php
                    $statusColors = [
                        'pending' => 'bg-yellow-200 text-yellow-900 font-semibold',
                        'processing' => 'bg-blue-200 text-blue-900 font-semibold',
                        'completed' => 'bg-green-200 text-green-900 font-semibold',
                        'failed' => 'bg-red-200 text-red-900 font-semibold',
                    ];
                @endphp
                <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold {{ $statusColors[$leadRequest->status] ?? 'bg-gray-100 text-gray-800' }}">
                    {{ ucfirst($leadRequest->status) }}
                </span>
            </div>
            <div>
                <div class="text-sm font-medium text-gray-600">Target Count</div>
                <div class="font-semibold text-gray-900">{{ $leadRequest->target_count }}</div>
            </div>
            <div>
                <div class="text-sm font-medium text-gray-600">Companies Found</div>
                <div class="font-semibold text-gray-900">{{ $leadRequest->companies_found }}</div>
            </div>
            <div>
                <div class="text-sm font-medium text-gray-600">Contacts Found</div>
                <div class="font-semibold text-gray-900">{{ $leadRequest->contacts_found }}</div>
            </div>
            @if($leadRequest->reference_company_url)
                <div>
                    <div class="text-sm font-medium text-gray-600">Reference URL</div>
                    <a href="{{ $leadRequest->reference_company_url }}" target="_blank" class="text-blue-600 hover:underline">
                        {{ $leadRequest->reference_company_url }}
                    </a>
                </div>
            @endif
            <div>
                <div class="text-sm font-medium text-gray-600">Target Job Titles</div>
                <div class="flex flex-wrap gap-1">
                    @foreach($leadRequest->target_job_titles ?? [] as $title)
                        <span class="rounded-full bg-blue-100 px-2 py-1 text-xs font-medium text-blue-800">{{ $title }}</span>
                    @endforeach
                </div>
            </div>
        </div>
            @if($leadRequest->error_message)
            <div class="mt-4 rounded-lg bg-red-50 p-4">
                <div class="text-sm font-semibold text-red-800">Error:</div>
                <div class="text-sm text-red-700">{{ $leadRequest->error_message }}</div>
            </div>
        @endif
    </div>

    <!-- ICP Profile & Search Criteria -->
    @if($leadRequest->icp_profile || $leadRequest->search_criteria)
        <div class="rounded-lg border-2 border-gray-300 bg-white p-6 shadow-sm">
            <div class="mb-4 flex items-center justify-between">
                <flux:heading size="lg">AI Analysis Results</flux:heading>
                <button 
                    wire:click="$toggle('showICP')"
                    class="text-sm text-blue-600 hover:text-blue-800 font-medium"
                >
                    {{ $showICP ?? false ? 'Hide Details' : 'Show Details' }}
                </button>
            </div>

            @if($leadRequest->icp_profile)
                <div class="mb-4">
                    <div class="text-sm font-semibold text-gray-700 mb-2">Primary Industry:</div>
                    <div class="text-lg font-bold text-gray-900">{{ $leadRequest->icp_profile['industry'] ?? 'N/A' }}</div>
                </div>
            @endif

            @if(($showICP ?? false) && $leadRequest->icp_profile)
                <div class="mt-4 space-y-4 rounded-lg border border-gray-200 bg-gray-50 p-4">
                    <div>
                        <div class="text-sm font-semibold text-gray-700 mb-1">Full ICP Profile:</div>
                        <pre class="text-xs text-gray-700 overflow-x-auto bg-white p-3 rounded border border-gray-200">{{ json_encode($leadRequest->icp_profile, JSON_PRETTY_PRINT) }}</pre>
                    </div>
                </div>
            @endif

            @if($leadRequest->search_criteria)
                <div class="mt-4">
                    <div class="text-sm font-semibold text-gray-700 mb-2">Search Criteria Used:</div>
                    <div class="space-y-2">
                        @if(!empty($leadRequest->search_criteria['industries']))
                            <div>
                                <span class="text-sm font-medium text-gray-600">Industries:</span>
                                <div class="flex flex-wrap gap-1 mt-1">
                                    @foreach($leadRequest->search_criteria['industries'] as $industry)
                                        <span class="rounded-full bg-blue-100 px-2 py-1 text-xs font-medium text-blue-800">{{ $industry }}</span>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                        @if(!empty($leadRequest->search_criteria['keywords']))
                            <div>
                                <span class="text-sm font-medium text-gray-600">Keywords:</span>
                                <div class="flex flex-wrap gap-1 mt-1">
                                    @foreach($leadRequest->search_criteria['keywords'] as $keyword)
                                        <span class="rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-800">{{ $keyword }}</span>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                        @if(!empty($leadRequest->search_criteria['technologies']))
                            <div>
                                <span class="text-sm font-medium text-gray-600">Technologies:</span>
                                <div class="flex flex-wrap gap-1 mt-1">
                                    @foreach($leadRequest->search_criteria['technologies'] as $tech)
                                        <span class="rounded-full bg-purple-100 px-2 py-1 text-xs font-medium text-purple-800">{{ $tech }}</span>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                    @if(($showICP ?? false))
                        <div class="mt-4">
                            <div class="text-sm font-semibold text-gray-700 mb-1">Full Search Criteria:</div>
                            <pre class="text-xs text-gray-700 overflow-x-auto bg-white p-3 rounded border border-gray-200">{{ json_encode($leadRequest->search_criteria, JSON_PRETTY_PRINT) }}</pre>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    @endif

    <!-- Tabs -->
    <div class="rounded-lg border-2 border-gray-300 bg-white p-6 shadow-sm">
        <!-- Tab Headers -->
        <div class="mb-6 border-b-2 border-gray-200">
            <div class="flex gap-4">
                <button 
                    wire:click="setTab('contacts')"
                    class="px-4 py-2 font-semibold border-b-2 transition-colors {{ $activeTab === 'contacts' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-600 hover:text-gray-900' }}"
                >
                    Contacts ({{ $results->total() }})
                </button>
                <button 
                    wire:click="setTab('companies')"
                    class="px-4 py-2 font-semibold border-b-2 transition-colors {{ $activeTab === 'companies' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-600 hover:text-gray-900' }}"
                >
                    Companies ({{ $companies->count() }})
                </button>
            </div>
        </div>

        <!-- Contacts Tab Content -->
        @if($activeTab === 'contacts')
            @if($results->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b-2 border-gray-300 bg-gray-50">
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900">Company</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900">Contact Name</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900">Title</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900">Email</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($results as $result)
                                <tr class="border-b border-gray-200 hover:bg-blue-50 transition-colors">
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-gray-900">{{ $result->company->name }}</div>
                                        @if($result->company->industry)
                                            <div class="text-sm text-gray-600">{{ $result->company->industry }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($result->person)
                                            <div class="font-medium text-gray-900">{{ $result->person->full_name }}</div>
                                        @else
                                            <span class="text-gray-500">No contact</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($result->person && $result->person->title)
                                            <span class="text-gray-900">{{ $result->person->title }}</span>
                                        @else
                                            <span class="text-gray-500">-</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($result->person && $result->person->email)
                                            <a href="mailto:{{ $result->person->email }}" class="text-blue-600 hover:underline font-medium">
                                                {{ $result->person->email }}
                                            </a>
                                        @else
                                            <span class="text-gray-500">-</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @php
                                            $statusColors = [
                                                'pending' => 'bg-gray-200 text-gray-900 font-semibold',
                                                'contacted' => 'bg-blue-200 text-blue-900 font-semibold',
                                                'responded' => 'bg-green-200 text-green-900 font-semibold',
                                                'converted' => 'bg-purple-200 text-purple-900 font-semibold',
                                                'rejected' => 'bg-red-200 text-red-900 font-semibold',
                                            ];
                                        @endphp
                                        <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold {{ $statusColors[$result->status] ?? 'bg-gray-100 text-gray-800' }}">
                                            {{ ucfirst($result->status) }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-4">
                    {{ $results->links() }}
                </div>
            @else
                <div class="py-12 text-center">
                    <p class="text-gray-600">
                        @if($leadRequest->status === 'processing')
                            Processing... Results will appear here once found.
                        @elseif($leadRequest->status === 'pending')
                            Waiting to start processing...
                        @else
                            No contacts found yet.
                        @endif
                    </p>
                </div>
            @endif
        @endif

        <!-- Companies Tab Content -->
        @if($activeTab === 'companies')
            @if($companies->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b-2 border-gray-300 bg-gray-50">
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900">Company Name</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900">Industry</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900">Domain</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900">Location</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900">Employees</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900">Contacts</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($companies as $company)
                                @php
                                    $contactsCount = $leadRequest->leadResults()
                                        ->where('company_id', $company->id)
                                        ->count();
                                @endphp
                                <tr class="border-b border-gray-200 hover:bg-blue-50 transition-colors">
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-gray-900">{{ $company->name }}</div>
                                        @if($company->website)
                                            <a href="{{ $company->website }}" target="_blank" class="text-xs text-blue-600 hover:underline">
                                                Visit Website
                                            </a>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="text-gray-900">{{ $company->industry ?? '-' }}</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($company->domain)
                                            <a href="https://{{ $company->domain }}" target="_blank" class="text-blue-600 hover:underline font-medium">
                                                {{ $company->domain }}
                                            </a>
                                        @else
                                            <span class="text-gray-500">-</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($company->city || $company->country)
                                            <span class="text-gray-900">
                                                {{ trim(($company->city ?? '') . ', ' . ($company->country ?? ''), ', ') }}
                                            </span>
                                        @else
                                            <span class="text-gray-500">-</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($company->employees)
                                            <span class="text-gray-900">{{ number_format($company->employees) }}</span>
                                        @else
                                            <span class="text-gray-500">-</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($contactsCount > 0)
                                            <span class="inline-flex rounded-full bg-blue-100 px-3 py-1 text-sm font-semibold text-blue-800">
                                                {{ $contactsCount }} {{ $contactsCount === 1 ? 'contact' : 'contacts' }}
                                            </span>
                                        @else
                                            <span class="text-gray-500">0</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="py-12 text-center">
                    <p class="text-gray-600">No companies found yet.</p>
                </div>
            @endif
        @endif
    </div>
</div>
