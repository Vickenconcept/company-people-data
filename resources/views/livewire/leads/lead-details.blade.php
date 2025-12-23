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
                <!-- Advanced Filters -->
                <div class="mb-4 rounded-lg border-2 border-gray-300 bg-white p-4 shadow-sm">
                    <div class="mb-2 flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-gray-700">Filters</h3>
                        @if($filterStatus || $filterIndustry || $filterJobTitle || $filterHasEmail !== null || $filterHasGeneratedEmail !== null || $filterTagId)
                            <button 
                                wire:click="clearFilters"
                                class="text-xs text-blue-600 hover:text-blue-800 font-medium"
                            >
                                Clear All
                            </button>
                        @endif
                    </div>
                    <div class="grid gap-3 md:grid-cols-3 lg:grid-cols-6">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
                            <select 
                                wire:model.live="filterStatus"
                                class="w-full text-sm rounded-lg border border-gray-300 px-2 py-1 focus:border-orange-500 focus:outline-none"
                            >
                                <option value="">All</option>
                                <option value="pending">Pending</option>
                                <option value="contacted">Contacted</option>
                                <option value="responded">Responded</option>
                                <option value="converted">Converted</option>
                                <option value="rejected">Rejected</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Industry</label>
                            <select 
                                wire:model.live="filterIndustry"
                                class="w-full text-sm rounded-lg border border-gray-300 px-2 py-1 focus:border-orange-500 focus:outline-none"
                            >
                                <option value="">All Industries</option>
                                @foreach($industries as $industry)
                                    <option value="{{ $industry }}">{{ $industry }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Job Title</label>
                            <select 
                                wire:model.live="filterJobTitle"
                                class="w-full text-sm rounded-lg border border-gray-300 px-2 py-1 focus:border-orange-500 focus:outline-none"
                            >
                                <option value="">All Titles</option>
                                @foreach($jobTitles as $title)
                                    <option value="{{ $title }}">{{ $title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Has Email</label>
                            <select 
                                wire:model.live="filterHasEmail"
                                class="w-full text-sm rounded-lg border border-gray-300 px-2 py-1 focus:border-orange-500 focus:outline-none"
                            >
                                <option value="">All</option>
                                <option value="1">Yes</option>
                                <option value="0">No</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Has Generated Email</label>
                            <select 
                                wire:model.live="filterHasGeneratedEmail"
                                class="w-full text-sm rounded-lg border border-gray-300 px-2 py-1 focus:border-orange-500 focus:outline-none"
                            >
                                <option value="">All</option>
                                <option value="1">Yes</option>
                                <option value="0">No</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Tag</label>
                            <select 
                                wire:model.live="filterTagId"
                                class="w-full text-sm rounded-lg border border-gray-300 px-2 py-1 focus:border-orange-500 focus:outline-none"
                            >
                                <option value="">All Tags</option>
                                @foreach($tags as $tag)
                                    <option value="{{ $tag->id }}">{{ $tag->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="mb-4 flex flex-wrap items-center gap-3">
                    <button 
                        wire:click="selectAll"
                        class="px-4 py-2 text-sm font-semibold bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200"
                    >
                        Select All
                    </button>
                    <button 
                        wire:click="deselectAll"
                        class="px-4 py-2 text-sm font-semibold bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200"
                    >
                        Deselect All
                    </button>
                    <span class="text-sm text-gray-600">
                        {{ count($selectedLeadResults) }} selected
                    </span>
                    <div class="flex-1"></div>
                    <button 
                        wire:click="openGenerateModal"
                        @if(empty($selectedLeadResults)) disabled @endif
                        class="px-4 py-2 text-sm font-semibold bg-orange-500 text-white rounded-lg hover:bg-orange-600 disabled:bg-gray-300 disabled:cursor-not-allowed"
                    >
                        Generate Emails
                    </button>
                    @php
                        $generatedCount = $leadRequest->leadResults()
                            ->whereIn('id', $selectedLeadResults)
                            ->whereHas('generatedEmail')
                            ->count();
                    @endphp
                    @if($generatedCount > 0)
                        <button 
                            wire:click="openQueueModal"
                            class="px-4 py-2 text-sm font-semibold bg-green-600 text-white rounded-lg hover:bg-green-700"
                        >
                            Queue & Send ({{ $generatedCount }})
                        </button>
                    @endif
                    @if(!empty($selectedLeadResults))
                        <button 
                            wire:click="openBulkStatusModal"
                            class="px-4 py-2 text-sm font-semibold bg-purple-600 text-white rounded-lg hover:bg-purple-700"
                        >
                            Update Status ({{ count($selectedLeadResults) }})
                        </button>
                    @endif
                    <button 
                        wire:click="openQueuedEmailsModal"
                        class="px-4 py-2 text-sm font-semibold bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                    >
                        View Queued Emails
                    </button>
                    <a 
                        href="{{ route('leads.email-templates') }}" 
                        wire:navigate
                        class="px-4 py-2 text-sm font-semibold bg-indigo-600 text-white rounded-lg hover:bg-indigo-700"
                    >
                        Manage Templates
                    </a>
                </div>

                <!-- Message Display -->
                @if($message)
                    <div class="mb-4 p-4 rounded-lg {{ $messageType === 'success' ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800' }}">
                        <div class="flex items-center justify-between">
                            <span>{{ $message }}</span>
                            <button wire:click="dismissMessage" class="text-sm font-semibold hover:underline">Dismiss</button>
                        </div>
                    </div>
                @endif

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b-2 border-gray-300 bg-gray-50">
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900 w-12">
                                    <input 
                                        type="checkbox" 
                                        wire:click="selectAll"
                                        class="rounded border-gray-300"
                                    >
                                </th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900">Company</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900">Contact Name</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900">Title</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900">Email</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900">Status</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900">Tags</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($results as $result)
                                @php
                                    $hasEmail = $result->person && $result->person->email;
                                    $isSelected = in_array($result->id, $selectedLeadResults);
                                    $isGenerating = in_array($result->id, $generatingLeadResultIds);
                                    $hasGeneratedEmail = $result->generatedEmail !== null;
                                @endphp
                                <tr class="border-b border-gray-200 hover:bg-blue-50 transition-colors {{ $isSelected ? 'bg-blue-100' : '' }}">
                                    <td class="px-4 py-3">
                                        @if($hasEmail)
                                            <input 
                                                type="checkbox" 
                                                wire:click="toggleSelect({{ $result->id }})"
                                                @if($isSelected) checked @endif
                                                class="rounded border-gray-300"
                                            >
                                        @endif
                                    </td>
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
                                        @if($hasEmail)
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
                                        <div class="flex items-center gap-2">
                                            <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold {{ $statusColors[$result->status] ?? 'bg-gray-100 text-gray-800' }}">
                                                {{ ucfirst($result->status) }}
                                            </span>
                                            <select 
                                                wire:change="updateLeadStatus({{ $result->id }}, $event.target.value)"
                                                class="text-xs border border-gray-300 rounded px-2 py-1 focus:outline-none focus:border-blue-500"
                                            >
                                                <option value="pending" {{ $result->status === 'pending' ? 'selected' : '' }}>Pending</option>
                                                <option value="contacted" {{ $result->status === 'contacted' ? 'selected' : '' }}>Contacted</option>
                                                <option value="responded" {{ $result->status === 'responded' ? 'selected' : '' }}>Responded</option>
                                                <option value="converted" {{ $result->status === 'converted' ? 'selected' : '' }}>Converted</option>
                                                <option value="rejected" {{ $result->status === 'rejected' ? 'selected' : '' }}>Rejected</option>
                                            </select>
                                        </div>
                                        @if($result->queuedEmails->count() > 0)
                                            @php
                                                $latestEmail = $result->queuedEmails->first();
                                                $emailStatusColors = [
                                                    'pending' => 'bg-yellow-100 text-yellow-800',
                                                    'sent' => 'bg-green-100 text-green-800',
                                                    'failed' => 'bg-red-100 text-red-800',
                                                ];
                                            @endphp
                                            <div class="mt-1">
                                                <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold {{ $emailStatusColors[$latestEmail->status] ?? 'bg-gray-100 text-gray-800' }}">
                                                    Email: {{ ucfirst($latestEmail->status) }}
                                                </span>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($result->tags->count() > 0)
                                            <div class="flex flex-wrap gap-1">
                                                @foreach($result->tags as $tag)
                                                    <span 
                                                        class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold text-white"
                                                        style="background-color: {{ $tag->color }}"
                                                    >
                                                        {{ $tag->name }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @endif
                                        <button 
                                            wire:click="openTagsModal({{ $result->id }})"
                                            class="mt-1 text-xs text-blue-600 hover:text-blue-800 font-medium"
                                        >
                                            {{ $result->tags->count() > 0 ? 'Edit Tags' : 'Add Tags' }}
                                        </button>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex flex-col gap-1">
                                            @if($isGenerating)
                                            <div class="flex items-center gap-2 text-sm text-orange-500">
                                                <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                <span>Generating...</span>
                                            </div>
                                        @elseif($hasGeneratedEmail)
                                            <button 
                                                wire:click="viewEmail({{ $result->id }})"
                                                class="px-3 py-1 text-sm font-semibold bg-blue-500 text-white rounded-lg hover:bg-blue-600"
                                            >
                                                View Email
                                            </button>
                                        @elseif($hasEmail)
                                            <button 
                                                wire:click="generateSingleEmail({{ $result->id }})"
                                                wire:loading.attr="disabled"
                                                class="px-3 py-1 text-sm font-semibold bg-orange-500 text-white rounded-lg hover:bg-orange-600 disabled:bg-gray-300"
                                            >
                                                <span wire:loading.remove wire:target="generateSingleEmail({{ $result->id }})">Generate Email</span>
                                                <span wire:loading wire:target="generateSingleEmail({{ $result->id }})">Generating...</span>
                                            </button>
                                        @endif
                                            <button 
                                                wire:click="openNotesModal({{ $result->id }})"
                                                class="px-2 py-1 text-xs font-semibold bg-gray-500 text-white rounded hover:bg-gray-600"
                                            >
                                                {{ $result->notes ? '📝 Edit Notes' : '📝 Add Notes' }}
                                            </button>
                                        </div>
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

    <!-- Generate Emails Modal -->
    @if($showGenerateModal)
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" wire:click="closeGenerateModal">
            <div class="bg-white rounded-lg p-6 max-w-3xl w-full mx-4 max-h-[90vh] overflow-y-auto" wire:click.stop>
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-2xl font-bold text-gray-900">Generate Emails</h2>
                    <button wire:click="closeGenerateModal" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                @if($emailTemplates->count() > 0)
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Email Template (Optional)
                        </label>
                        <select 
                            wire:model.live="selectedTemplateId"
                            class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 focus:border-orange-500 focus:outline-none"
                        >
                            <option value="">No Template</option>
                            @foreach($emailTemplates as $template)
                                <option value="{{ $template->id }}">{{ $template->name }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Select a template to pre-fill context.</p>
                    </div>
                @endif

                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Custom Context (Optional)
                    </label>
                    <textarea 
                        wire:model="customContext"
                        rows="4"
                        class="w-full border-2 border-gray-300 rounded-lg p-3 focus:border-orange-500 focus:outline-none"
                        placeholder="Add any custom context or instructions for the AI to use when generating emails..."
                    ></textarea>
                    <p class="text-xs text-gray-500 mt-1">This context will be used by AI to personalize all generated emails.</p>
                </div>

                <div class="flex justify-end gap-3">
                    <button 
                        wire:click="closeGenerateModal"
                        class="px-4 py-2 text-sm font-semibold bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200"
                    >
                        Cancel
                    </button>
                    <button 
                        wire:click="startGenerating"
                        wire:loading.attr="disabled"
                        class="px-4 py-2 text-sm font-semibold bg-orange-500 text-white rounded-lg hover:bg-orange-600 disabled:bg-gray-300"
                    >
                        Start Generating
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Queue Emails Confirmation Modal -->
    @if($showQueueModal)
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" wire:click="closeQueueModal">
            <div class="bg-white rounded-lg p-6 max-w-2xl w-full mx-4" wire:click.stop>
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-2xl font-bold text-gray-900">Queue Emails for Sending</h2>
                    <button wire:click="closeQueueModal" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                @php
                    $selectedWithEmails = $leadRequest->leadResults()
                        ->whereIn('id', $selectedLeadResults)
                        ->whereHas('generatedEmail')
                        ->count();
                @endphp
                <p class="text-gray-700 mb-4">
                    Are you sure you want to queue <strong>{{ $selectedWithEmails }} email(s)</strong> for sending? 
                    They will be sent in the background.
                </p>

                <div class="flex justify-end gap-3">
                    <button 
                        wire:click="closeQueueModal"
                        class="px-4 py-2 text-sm font-semibold bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200"
                    >
                        Cancel
                    </button>
                    <button 
                        wire:click="queueSelectedEmails"
                        wire:loading.attr="disabled"
                        class="px-4 py-2 text-sm font-semibold bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:bg-gray-300"
                    >
                        <span wire:loading.remove>Queue & Send</span>
                        <span wire:loading>Queueing...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Queued Emails Modal -->
    @if($showQueuedEmailsModal)
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" wire:click="closeQueuedEmailsModal">
            <div class="bg-white rounded-lg p-6 max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto" wire:click.stop>
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-2xl font-bold text-gray-900">Queued Emails</h2>
                    <button wire:click="closeQueuedEmailsModal" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                @if($queuedEmails->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b-2 border-gray-300 bg-gray-50">
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900">To</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900">Subject</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900">Status</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900">Queued At</th>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-900">Sent At</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($queuedEmails as $queuedEmail)
                                    @php
                                        $statusColors = [
                                            'pending' => 'bg-yellow-100 text-yellow-800',
                                            'sent' => 'bg-green-100 text-green-800',
                                            'failed' => 'bg-red-100 text-red-800',
                                        ];
                                    @endphp
                                    <tr class="border-b border-gray-200">
                                        <td class="px-4 py-3">
                                            <div class="font-medium text-gray-900">{{ $queuedEmail->to_email }}</div>
                                            @if($queuedEmail->leadResult && $queuedEmail->leadResult->person)
                                                <div class="text-sm text-gray-600">{{ $queuedEmail->leadResult->person->full_name }}</div>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="text-gray-900">@php echo \Illuminate\Support\Str::limit($queuedEmail->subject, 50); @endphp</div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold {{ $statusColors[$queuedEmail->status] ?? 'bg-gray-100 text-gray-800' }}">
                                                {{ ucfirst($queuedEmail->status) }}
                                            </span>
                                            @if($queuedEmail->status === 'failed' && $queuedEmail->error_message)
                                                <div class="text-xs text-red-600 mt-1">@php echo \Illuminate\Support\Str::limit($queuedEmail->error_message, 50); @endphp</div>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-600">
                                            {{ $queuedEmail->created_at->format('M d, Y H:i') }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-600">
                                            @if($queuedEmail->sent_at)
                                                {{ $queuedEmail->sent_at->format('M d, Y H:i') }}
                                            @else
                                                <span class="text-gray-400">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="py-12 text-center">
                        <p class="text-gray-600">No queued emails found.</p>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- Individual Email View Modal -->
    @if($showEmailModal && $viewingEmail)
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" wire:click="closeEmailModal">
            <div class="bg-white rounded-lg p-6 max-w-3xl w-full mx-4 max-h-[90vh] overflow-y-auto" wire:click.stop>
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-2xl font-bold text-gray-900">Generated Email</h2>
                    <button wire:click="closeEmailModal" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                @if($viewingEmail->leadResult && $viewingEmail->leadResult->person)
                    <div class="mb-4 p-4 bg-gray-50 rounded-lg">
                        <div class="text-sm font-semibold text-gray-700">To:</div>
                        <div class="text-lg font-bold text-gray-900">{{ $viewingEmail->leadResult->person->full_name }}</div>
                        <div class="text-sm text-gray-600">{{ $viewingEmail->leadResult->person->email }}</div>
                        @if($viewingEmail->leadResult->company)
                            <div class="text-sm text-gray-600 mt-1">{{ $viewingEmail->leadResult->company->name }}</div>
                        @endif
                    </div>
                @endif

                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Subject</label>
                    <input 
                        type="text"
                        value="{{ $viewingEmail->subject }}"
                        wire:blur="updateEmailSubject($event.target.value)"
                        class="w-full border-2 border-gray-300 rounded-lg p-3 focus:border-orange-500 focus:outline-none"
                    >
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Body</label>
                    <textarea 
                        rows="12"
                        wire:blur="updateEmailBody($event.target.value)"
                        class="w-full border-2 border-gray-300 rounded-lg p-3 focus:border-orange-500 focus:outline-none"
                    >{{ $viewingEmail->body }}</textarea>
                </div>

                <div class="flex justify-between items-center">
                    <button 
                        wire:click="regenerateEmail"
                        wire:loading.attr="disabled"
                        class="px-4 py-2 text-sm font-semibold bg-orange-500 text-white rounded-lg hover:bg-orange-600 disabled:bg-gray-300"
                    >
                        <span wire:loading.remove>Regenerate Email</span>
                        <span wire:loading>Regenerating...</span>
                    </button>
                    <div class="flex gap-3">
                        <button 
                            wire:click="closeEmailModal"
                            class="px-4 py-2 text-sm font-semibold bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200"
                        >
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Bulk Status Update Modal -->
    @if($showBulkStatusModal)
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" wire:click="closeBulkStatusModal">
            <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4" wire:click.stop>
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-2xl font-bold text-gray-900">Update Status</h2>
                    <button wire:click="closeBulkStatusModal" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <p class="text-gray-700 mb-4">
                    Update status for <strong>{{ count($selectedLeadResults) }} selected contact(s)</strong>:
                </p>

                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">New Status</label>
                    <select 
                        wire:model="bulkStatus"
                        class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 focus:border-orange-500 focus:outline-none"
                    >
                        <option value="">Select Status...</option>
                        <option value="pending">Pending</option>
                        <option value="contacted">Contacted</option>
                        <option value="responded">Responded</option>
                        <option value="converted">Converted</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>

                <div class="flex justify-end gap-3">
                    <button 
                        wire:click="closeBulkStatusModal"
                        class="px-4 py-2 text-sm font-semibold bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200"
                    >
                        Cancel
                    </button>
                    <button 
                        wire:click="updateBulkStatus"
                        wire:loading.attr="disabled"
                        class="px-4 py-2 text-sm font-semibold bg-purple-600 text-white rounded-lg hover:bg-purple-700 disabled:bg-gray-300"
                    >
                        <span wire:loading.remove>Update Status</span>
                        <span wire:loading>Updating...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Notes Modal -->
    @if($showNotesModal && $editingNotesLeadResultId)
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" wire:click="closeNotesModal">
            <div class="bg-white rounded-lg p-6 max-w-2xl w-full mx-4" wire:click.stop>
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-2xl font-bold text-gray-900">Notes</h2>
                    <button wire:click="closeNotesModal" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <div class="mb-4">
                    <textarea 
                        wire:model="notesContent"
                        rows="10"
                        class="w-full border-2 border-gray-300 rounded-lg p-3 focus:border-orange-500 focus:outline-none"
                        placeholder="Add notes about this contact..."
                    ></textarea>
                </div>

                <div class="flex justify-end gap-3">
                    <button 
                        wire:click="closeNotesModal"
                        class="px-4 py-2 text-sm font-semibold bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200"
                    >
                        Cancel
                    </button>
                    <button 
                        wire:click="saveNotes"
                        class="px-4 py-2 text-sm font-semibold bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                    >
                        Save Notes
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Tags Modal -->
    @if($showTagsModal && $taggingLeadResultId)
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" wire:click="closeTagsModal">
            <div class="bg-white rounded-lg p-6 max-w-2xl w-full mx-4" wire:click.stop>
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-2xl font-bold text-gray-900">Manage Tags</h2>
                    <button wire:click="closeTagsModal" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Create New Tag -->
                <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                    <h3 class="text-sm font-semibold text-gray-700 mb-3">Create New Tag</h3>
                    <div class="flex gap-2">
                        <input 
                            type="text"
                            wire:model="newTagName"
                            wire:keydown.enter="createTag"
                            placeholder="Tag name..."
                            class="flex-1 border-2 border-gray-300 rounded-lg px-3 py-2 focus:border-orange-500 focus:outline-none"
                        >
                        <input 
                            type="color"
                            wire:model="newTagColor"
                            class="w-20 h-10 border-2 border-gray-300 rounded cursor-pointer"
                        >
                        <button 
                            wire:click="createTag"
                            class="px-4 py-2 text-sm font-semibold bg-orange-500 text-white rounded-lg hover:bg-orange-600"
                        >
                            Create
                        </button>
                    </div>
                </div>

                <!-- Select Tags -->
                <div class="mb-4">
                    <h3 class="text-sm font-semibold text-gray-700 mb-3">Select Tags</h3>
                    <div class="space-y-2 max-h-64 overflow-y-auto">
                        @foreach($tags as $tag)
                            <label class="flex items-center gap-2 p-2 hover:bg-gray-50 rounded cursor-pointer">
                                <input 
                                    type="checkbox"
                                    wire:model="selectedTagIds"
                                    value="{{ $tag->id }}"
                                    class="rounded border-gray-300"
                                >
                                <span 
                                    class="inline-flex items-center rounded-full px-3 py-1 text-sm font-semibold text-white"
                                    style="background-color: {{ $tag->color }}"
                                >
                                    {{ $tag->name }}
                                </span>
                                <button 
                                    wire:click="deleteTag({{ $tag->id }})"
                                    wire:confirm="Delete this tag from all leads?"
                                    class="ml-auto text-red-600 hover:text-red-800 text-xs"
                                >
                                    Delete
                                </button>
                            </label>
                        @endforeach
                        @if($tags->isEmpty())
                            <p class="text-sm text-gray-500">No tags yet. Create one above.</p>
                        @endif
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <button 
                        wire:click="closeTagsModal"
                        class="px-4 py-2 text-sm font-semibold bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200"
                    >
                        Cancel
                    </button>
                    <button 
                        wire:click="saveTags"
                        class="px-4 py-2 text-sm font-semibold bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                    >
                        Save Tags
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
