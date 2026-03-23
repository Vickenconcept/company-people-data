<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="flex items-start gap-3">
            <a 
                href="{{ route('leads.dashboard') }}" 
                wire:navigate
                class="flex items-center justify-center w-10 h-10 rounded-full bg-white border border-slate-200 text-slate-600 hover:border-orange-300 hover:bg-orange-50 hover:text-orange-600 transition-all shadow-sm cursor-pointer no-underline"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-slate-900 leading-tight">{{ $leadRequest->reference_company_name }}</h1>
                <p class="text-sm text-slate-500 mt-1">Lead discovery campaign &middot; Created {{ $leadRequest->created_at->diffForHumans() }}</p>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-slate-50 to-white border border-slate-100 shadow-sm p-5">
            <div class="relative z-10">
                <div class="flex items-center gap-2 mb-1">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Target</span>
                </div>
                <div class="text-3xl font-bold text-slate-900">{{ $leadRequest->target_count }}</div>
                <p class="text-xs text-slate-500 mt-1.5">companies requested</p>
            </div>
        </div>
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-orange-50 to-white border border-orange-100 shadow-sm p-5">
            <div class="relative z-10">
                <div class="flex items-center gap-2 mb-1">
                    <svg class="w-4 h-4 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    <span class="text-xs font-semibold text-orange-500 uppercase tracking-wider">Companies</span>
                </div>
                <div class="text-3xl font-bold text-orange-600">{{ $leadRequest->companies_found }}</div>
                <p class="text-xs text-slate-500 mt-1.5">discovered</p>
            </div>
        </div>
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-slate-50 to-white border border-slate-100 shadow-sm p-5">
            <div class="relative z-10">
                <div class="flex items-center gap-2 mb-1">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Contacts</span>
                </div>
                <div class="text-3xl font-bold text-slate-900">{{ $leadRequest->contacts_found }}</div>
                <p class="text-xs text-slate-500 mt-1.5">people found</p>
            </div>
        </div>
        <div class="relative overflow-hidden rounded-2xl bg-white border border-slate-100 shadow-sm p-5">
            <div class="relative z-10">
                @php
                    $statusConfig = [
                        'pending' => ['dot' => 'bg-amber-400', 'bg' => 'bg-amber-50', 'text' => 'text-amber-700', 'ring' => 'ring-amber-100'],
                        'processing' => ['dot' => 'bg-sky-400', 'bg' => 'bg-sky-50', 'text' => 'text-sky-700', 'ring' => 'ring-sky-100'],
                        'completed' => ['dot' => 'bg-emerald-400', 'bg' => 'bg-emerald-50', 'text' => 'text-emerald-700', 'ring' => 'ring-emerald-100'],
                        'failed' => ['dot' => 'bg-red-400', 'bg' => 'bg-red-50', 'text' => 'text-red-700', 'ring' => 'ring-red-100'],
                    ];
                    $sc = $statusConfig[$leadRequest->status] ?? $statusConfig['pending'];
                @endphp
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</span>
                </div>
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-semibold ring-1 {{ $sc['bg'] }} {{ $sc['text'] }} {{ $sc['ring'] }}">
                    <span class="w-1.5 h-1.5 rounded-full {{ $sc['dot'] }} animate-pulse"></span>
                    {{ ucfirst($leadRequest->status) }}
                </span>
            </div>
        </div>
    </div>
    
    <!-- Campaign Details -->
    <div class="rounded-2xl border border-slate-100 bg-white shadow-sm">
        <div class="px-6 py-4 border-b border-slate-100">
            <h2 class="text-sm font-semibold text-slate-700">Campaign Details</h2>
        </div>
        <div class="p-6 space-y-4 flex flex-col md:flex-row md:items-start md:gap-12">
            @if($leadRequest->reference_company_url)
                <div>
                    <div class="text-xs text-slate-400 font-medium mb-2">Reference URL</div>
                    <a href="{{ $leadRequest->reference_company_url }}" target="_blank" class="inline-flex items-center gap-1.5 text-sm font-medium text-orange-600 hover:text-orange-700 transition-colors no-underline">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                        {{ parse_url($leadRequest->reference_company_url, PHP_URL_HOST) ?? $leadRequest->reference_company_url }}
                    </a>
                </div>
            @endif
            <div>
                <div class="text-xs text-slate-400 font-medium mb-2">Target Job Titles</div>
                <div class="flex flex-wrap gap-2">
                    @foreach($leadRequest->target_job_titles ?? [] as $title)
                        <span class="px-3 py-1.5 rounded-full bg-orange-50 text-orange-700 text-xs font-medium border border-orange-100">{{ $title }}</span>
                    @endforeach
                </div>
            </div>
        </div>
        @if($leadRequest->error_message)
            <div class="mx-6 mb-4 rounded-xl bg-red-50 border border-red-100 px-4 py-3.5 flex gap-3">
                <svg class="w-5 h-5 text-red-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <div class="flex-1">
                    <div class="text-sm font-semibold text-red-800">Error occurred</div>
                    <div class="text-sm text-red-700 mt-0.5">{{ $leadRequest->error_message }}</div>
                </div>
            </div>
        @endif
    </div>

    <!-- ICP Profile & Search Criteria -->
    @if($leadRequest->icp_profile || $leadRequest->search_criteria)
        <div class="rounded-2xl border border-slate-100 bg-white shadow-sm overflow-hidden">
            <div class="px-6 py-4 bg-gradient-to-r from-violet-500 to-purple-600 flex items-center justify-between">
                <div class="flex items-center gap-2 text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                    <h2 class="text-sm font-semibold">AI Analysis Results</h2>
                </div>
                <button 
                    wire:click="$toggle('showICP')"
                    class="text-sm text-white hover:text-violet-100 font-medium cursor-pointer transition-colors"
                >
                    {{ $showICP ?? false ? 'Hide Details' : 'Show Details' }}
                </button>
            </div>
            <div class="p-6">

            @if($leadRequest->icp_profile)
                <div class="mb-4">
                    <div class="text-xs text-slate-400 font-medium mb-2">Primary Industry</div>
                    <div class="text-2xl font-bold text-slate-900">{{ $leadRequest->icp_profile['industry'] ?? 'N/A' }}</div>
                </div>
            @endif

            @if(($showICP ?? false) && $leadRequest->icp_profile)
                <div class="mt-4 space-y-4 rounded-xl border border-violet-100 bg-violet-50/30 p-4">
                    <div>
                        <div class="text-sm font-semibold text-slate-700 mb-2">Full ICP Profile</div>
                        <pre class="text-xs text-slate-700 overflow-x-auto bg-white p-4 rounded-xl border border-slate-200">{{ json_encode($leadRequest->icp_profile, JSON_PRETTY_PRINT) }}</pre>
                    </div>
                </div>
            @endif

            @if($leadRequest->search_criteria)
                <div class="mt-4">
                    <div class="text-sm font-semibold text-slate-700 mb-3">Search Criteria Used</div>
                    <div class="space-y-3">
                        @if(!empty($leadRequest->search_criteria['industries']))
                            <div>
                                <span class="text-xs font-medium text-slate-500 uppercase tracking-wider">Industries</span>
                                <div class="flex flex-wrap gap-2 mt-2">
                                    @foreach($leadRequest->search_criteria['industries'] as $industry)
                                        <span class="px-3 py-1.5 rounded-full bg-sky-50 text-sky-700 text-xs font-medium border border-sky-100">{{ $industry }}</span>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                        @if(!empty($leadRequest->search_criteria['keywords']))
                            <div>
                                <span class="text-xs font-medium text-slate-500 uppercase tracking-wider">Keywords</span>
                                <div class="flex flex-wrap gap-2 mt-2">
                                    @foreach($leadRequest->search_criteria['keywords'] as $keyword)
                                        <span class="px-3 py-1.5 rounded-full bg-emerald-50 text-emerald-700 text-xs font-medium border border-emerald-100">{{ $keyword }}</span>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                        @if(!empty($leadRequest->search_criteria['technologies']))
                            <div>
                                <span class="text-xs font-medium text-slate-500 uppercase tracking-wider">Technologies</span>
                                <div class="flex flex-wrap gap-2 mt-2">
                                    @foreach($leadRequest->search_criteria['technologies'] as $tech)
                                        <span class="px-3 py-1.5 rounded-full bg-purple-50 text-purple-700 text-xs font-medium border border-purple-100">{{ $tech }}</span>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                    @if(($showICP ?? false))
                        <div class="mt-4">
                            <div class="text-sm font-semibold text-slate-700 mb-2">Full Search Criteria</div>
                            <pre class="text-xs text-slate-700 overflow-x-auto bg-white p-4 rounded-xl border border-slate-200">{{ json_encode($leadRequest->search_criteria, JSON_PRETTY_PRINT) }}</pre>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
    @endif

    <!-- Tabs -->
    <div class="rounded-2xl border border-slate-100 bg-white shadow-sm overflow-hidden">
        <!-- Tab Headers -->
        <div class="border-b border-slate-100">
            <div class="flex gap-1 px-6">
                <button 
                    wire:click="setTab('contacts')"
                    class="px-6 py-4 text-sm font-semibold transition-all relative cursor-pointer {{ $activeTab === 'contacts' ? 'text-orange-600' : 'text-slate-500 hover:text-slate-900' }}"
                >
                    Contacts ({{ $results->total() }})
                    @if($activeTab === 'contacts')
                        <span class="absolute inset-x-0 bottom-0 h-0.5 bg-gradient-to-r from-orange-400 to-orange-600 rounded-full"></span>
                    @endif
                </button>
                <button 
                    wire:click="setTab('companies')"
                    class="px-6 py-4 text-sm font-semibold transition-all relative cursor-pointer {{ $activeTab === 'companies' ? 'text-orange-600' : 'text-slate-500 hover:text-slate-900' }}"
                >
                    Companies ({{ $companies->count() }})
                    @if($activeTab === 'companies')
                        <span class="absolute inset-x-0 bottom-0 h-0.5 bg-gradient-to-r from-orange-400 to-orange-600 rounded-full"></span>
                    @endif
                </button>
            </div>
        </div>

        <!-- Contacts Tab Content -->
        @if($activeTab === 'contacts')
            @if($results->count() > 0)
                <!-- Advanced Filters -->
                <div class="p-6 bg-slate-50 border-b border-slate-100">
                    <div class="mb-4 flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-slate-700">Filters</h3>
                        @if($filterStatus || $filterIndustry || $filterJobTitle || $filterHasEmail !== null || $filterHasGeneratedEmail !== null || $filterTagId)
                            <button 
                                wire:click="clearFilters"
                                class="text-xs text-orange-600 hover:text-orange-700 font-medium cursor-pointer transition-colors"
                            >
                                Clear All
                            </button>
                        @endif
                    </div>
                    <div class="grid gap-3 md:grid-cols-3 lg:grid-cols-6">
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1.5">Status</label>
                            <select 
                                wire:model.live="filterStatus"
                                class="w-full text-sm rounded-lg border border-slate-200 bg-white px-3 py-2 focus:border-orange-500 focus:ring-1 focus:ring-orange-500 focus:outline-none transition-colors"
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
                            <label class="block text-xs font-medium text-slate-500 mb-1.5">Industry</label>
                            <select 
                                wire:model.live="filterIndustry"
                                class="w-full text-sm rounded-lg border border-slate-200 bg-white px-3 py-2 focus:border-orange-500 focus:ring-1 focus:ring-orange-500 focus:outline-none transition-colors"
                            >
                                <option value="">All Industries</option>
                                @foreach($industries as $industry)
                                    <option value="{{ $industry }}">{{ $industry }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1.5">Job Title</label>
                            <select 
                                wire:model.live="filterJobTitle"
                                class="w-full text-sm rounded-lg border border-slate-200 bg-white px-3 py-2 focus:border-orange-500 focus:ring-1 focus:ring-orange-500 focus:outline-none transition-colors"
                            >
                                <option value="">All Titles</option>
                                @foreach($jobTitles as $title)
                                    <option value="{{ $title }}">{{ $title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1.5">Has Email</label>
                            <select 
                                wire:model.live="filterHasEmail"
                                class="w-full text-sm rounded-lg border border-slate-200 bg-white px-3 py-2 focus:border-orange-500 focus:ring-1 focus:ring-orange-500 focus:outline-none transition-colors"
                            >
                                <option value="">All</option>
                                <option value="1">Yes</option>
                                <option value="0">No</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1.5">Has Generated Email</label>
                            <select 
                                wire:model.live="filterHasGeneratedEmail"
                                class="w-full text-sm rounded-lg border border-slate-200 bg-white px-3 py-2 focus:border-orange-500 focus:ring-1 focus:ring-orange-500 focus:outline-none transition-colors"
                            >
                                <option value="">All</option>
                                <option value="1">Yes</option>
                                <option value="0">No</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1.5">Tag</label>
                            <select 
                                wire:model.live="filterTagId"
                                class="w-full text-sm rounded-lg border border-slate-200 bg-white px-3 py-2 focus:border-orange-500 focus:ring-1 focus:ring-orange-500 focus:outline-none transition-colors"
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
                <div class="px-6 py-4 flex flex-wrap items-center gap-3 border-b border-slate-100">
                    @php
                        $massSendAvailableCount = $leadRequest->leadResults()
                            ->whereHas('generatedEmail')
                            ->whereHas('person', function ($query) {
                                $query->whereNotNull('email')->where('email', '!=', '');
                            })
                            ->whereDoesntHave('queuedEmails', function ($query) {
                                $query->whereIn('status', ['pending', 'sent']);
                            })
                            ->count();
                    @endphp
                    <button 
                        wire:click="selectAll"
                        class="px-4 py-2 text-sm font-semibold bg-slate-100 text-slate-700 rounded-full hover:bg-slate-200 transition-colors cursor-pointer"
                    >
                        Select All
                    </button>
                    <button 
                        wire:click="deselectAll"
                        class="px-4 py-2 text-sm font-semibold bg-slate-100 text-slate-700 rounded-full hover:bg-slate-200 transition-colors cursor-pointer"
                    >
                        Deselect All
                    </button>
                    <span class="text-sm font-medium text-slate-600">
                        {{ count($selectedLeadResults) }} selected
                    </span>
                    <div class="flex-1"></div>
                    <button 
                        wire:click="openGenerateModal"
                        @if(empty($selectedLeadResults)) disabled @endif
                        class="px-4 py-2 text-sm font-semibold bg-gradient-to-r from-orange-500 to-orange-600 text-white rounded-full hover:from-orange-600 hover:to-orange-700 disabled:from-slate-300 disabled:to-slate-300 disabled:cursor-not-allowed transition-all shadow-sm cursor-pointer"
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
                            class="px-4 py-2 text-sm font-semibold bg-gradient-to-r from-emerald-500 to-emerald-600 text-white rounded-full hover:from-emerald-600 hover:to-emerald-700 transition-all shadow-sm cursor-pointer"
                        >
                            Queue & Send ({{ $generatedCount }})
                        </button>
                    @endif
                    @if($massSendAvailableCount > 0)
                        <button 
                            wire:click="openMassSendModal"
                            class="px-4 py-2 text-sm font-semibold bg-gradient-to-r from-teal-500 to-teal-600 text-white rounded-full hover:from-teal-600 hover:to-teal-700 transition-all shadow-sm cursor-pointer"
                        >
                            Mass Send Generated ({{ $massSendAvailableCount }})
                        </button>
                    @endif
                    @if(!empty($selectedLeadResults))
                        <button 
                            wire:click="openBulkStatusModal"
                            class="px-4 py-2 text-sm font-semibold bg-gradient-to-r from-purple-500 to-purple-600 text-white rounded-full hover:from-purple-600 hover:to-purple-700 transition-all shadow-sm cursor-pointer"
                        >
                            Update Status ({{ count($selectedLeadResults) }})
                        </button>
                    @endif
                    <button 
                        wire:click="openQueuedEmailsModal"
                        class="px-4 py-2 text-sm font-semibold bg-gradient-to-r from-sky-500 to-sky-600 text-white rounded-full hover:from-sky-600 hover:to-sky-700 transition-all shadow-sm cursor-pointer"
                    >
                        View Queued Emails
                    </button>
                    <a 
                        href="{{ route('leads.email-templates') }}" 
                        wire:navigate
                        class="inline-block px-4 py-2 text-sm font-semibold bg-gradient-to-r from-indigo-500 to-indigo-600 !text-white !hover:text-white rounded-full hover:from-indigo-600 hover:to-indigo-700 transition-all shadow-sm cursor-pointer no-underline"
                    >
                        Manage Templates
                    </a>
                </div>

                <!-- Message Display -->
                @if($message)
                    <div class="mx-6 my-4 p-4 rounded-xl {{ $messageType === 'success' ? 'bg-emerald-50 border border-emerald-100' : 'bg-red-50 border border-red-100' }} flex items-center justify-between">
                        <span class="text-sm font-medium {{ $messageType === 'success' ? 'text-emerald-800' : 'text-red-800' }}">{{ $message }}</span>
                        <button wire:click="dismissMessage" class="text-sm font-semibold {{ $messageType === 'success' ? 'text-emerald-600 hover:text-emerald-700' : 'text-red-600 hover:text-red-700' }} cursor-pointer transition-colors">Dismiss</button>
                    </div>
                @endif

                <div class="">
                    <table class="w-full table-fixed">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200">
                                <th class="px-4 py-4 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider w-10">
                                    <input 
                                        type="checkbox" 
                                        wire:click="selectAll"
                                        class="rounded border-slate-300 text-orange-600 focus:ring-orange-500 cursor-pointer"
                                    >
                                </th>
                                <th class="px-4 py-4 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider w-1/5">Company</th>
                                <th class="px-4 py-4 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider w-1/3">Contact Info</th>
                                <th class="px-4 py-4 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider w-1/5">Status & Tags</th>
                                <th class="px-4 py-4 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider w-1/5">Actions</th>
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
                                <tr class="group border-b border-slate-100 hover:bg-gradient-to-r hover:from-orange-50/40 hover:to-transparent transition-all duration-150 {{ $isSelected ? 'bg-gradient-to-r from-orange-50 to-transparent' : 'bg-white' }}">
                                    <td class="px-4 py-4 align-top">
                                        @if($hasEmail)
                                            <input 
                                                type="checkbox" 
                                                wire:click="toggleSelect({{ $result->id }})"
                                                @if($isSelected) checked @endif
                                                class="w-4 h-4 rounded border-2 border-slate-300 text-orange-600 focus:ring-2 focus:ring-orange-500 focus:ring-offset-1 cursor-pointer transition-all"
                                            >
                                        @endif
                                    </td>
                                    
                                    <!-- Company Column -->
                                    <td class="px-4 py-4 align-top">
                                        <div class="flex items-start gap-2.5">
                                            <div class="flex-shrink-0 w-9 h-9 rounded-lg bg-gradient-to-br from-orange-400 to-orange-500 flex items-center justify-center text-white font-bold text-xs shadow-sm">
                                                {{ strtoupper(substr($result->company->name, 0, 1)) }}
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="font-semibold text-slate-900 text-sm truncate">{{ $result->company->name }}</div>
                                                @if($result->company->industry)
                                                    <div class="text-xs text-slate-500 mt-0.5 truncate">{{ $result->company->industry }}</div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    
                                    <!-- Contact Info Column (merged: name, title, email) -->
                                    <td class="px-4 py-4 align-top">
                                        <div class="space-y-2">
                                            @if($result->person)
                                                <div class="flex items-center gap-2">
                                                    <div class="flex-shrink-0 w-7 h-7 rounded-full bg-gradient-to-br from-slate-200 to-slate-300 flex items-center justify-center text-slate-700 font-semibold text-xs">
                                                        {{ strtoupper(substr($result->person->full_name, 0, 1)) }}
                                                    </div>
                                                    <div class="flex-1 min-w-0">
                                                        <div class="font-medium text-slate-900 text-sm truncate">{{ $result->person->full_name }}</div>
                                                        @if($result->person->title)
                                                            <div class="text-xs text-slate-500 truncate mt-0.5">{{ $result->person->title }}</div>
                                                        @endif
                                                    </div>
                                                </div>
                                                @if($hasEmail)
                                                    <a href="mailto:{{ $result->person->email }}" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-orange-50 hover:bg-orange-100 border border-orange-200 transition-all text-xs text-orange-700 font-medium truncate max-w-full no-underline">
                                                        <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                                        <span class="truncate">{{ $result->person->email }}</span>
                                                    </a>
                                                @endif
                                            @else
                                                <span class="text-slate-400 text-xs italic">No contact</span>
                                            @endif
                                        </div>
                                    </td>
                                    
                                    <!-- Status & Tags Column (merged) -->
                                    <td class="px-4 py-4 align-top">
                                        <div class="space-y-2">
                                            @php
                                                $statusConfig = [
                                                    'pending' => ['bg' => 'bg-amber-50', 'text' => 'text-amber-700', 'ring' => 'ring-amber-200', 'dot' => 'bg-amber-400'],
                                                    'contacted' => ['bg' => 'bg-sky-50', 'text' => 'text-sky-700', 'ring' => 'ring-sky-200', 'dot' => 'bg-sky-400'],
                                                    'responded' => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-700', 'ring' => 'ring-emerald-200', 'dot' => 'bg-emerald-400'],
                                                    'converted' => ['bg' => 'bg-purple-50', 'text' => 'text-purple-700', 'ring' => 'ring-purple-200', 'dot' => 'bg-purple-400'],
                                                    'rejected' => ['bg' => 'bg-red-50', 'text' => 'text-red-700', 'ring' => 'ring-red-200', 'dot' => 'bg-red-400'],
                                                ];
                                                $sc = $statusConfig[$result->status] ?? ['bg' => 'bg-slate-50', 'text' => 'text-slate-700', 'ring' => 'ring-slate-200', 'dot' => 'bg-slate-400'];
                                            @endphp
                                            
                                            <div class="flex items-center gap-1.5">
                                                <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $sc['bg'] }} {{ $sc['text'] }} {{ $sc['ring'] }}">
                                                    <span class="w-1.5 h-1.5 rounded-full {{ $sc['dot'] }}"></span>
                                                    <span class="truncate">{{ ucfirst($result->status) }}</span>
                                                </span>
                                                <select 
                                                    wire:change="updateLeadStatus({{ $result->id }}, $event.target.value)"
                                                    class="text-xs border border-slate-200 rounded-lg px-2 py-1 focus:outline-none focus:border-orange-500 focus:ring-1 focus:ring-orange-500/20 bg-white hover:bg-slate-50 cursor-pointer transition-colors flex-shrink-0"
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
                                                    $emailConfig = [
                                                        'pending' => ['bg' => 'bg-amber-50', 'text' => 'text-amber-600', 'icon' => '⏳'],
                                                        'sent' => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-600', 'icon' => '✓'],
                                                        'failed' => ['bg' => 'bg-red-50', 'text' => 'text-red-600', 'icon' => '✗'],
                                                    ];
                                                    $ec = $emailConfig[$latestEmail->status] ?? ['bg' => 'bg-slate-50', 'text' => 'text-slate-600', 'icon' => '•'];
                                                @endphp
                                                <span class="inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-xs font-medium {{ $ec['bg'] }} {{ $ec['text'] }}">
                                                    {{ $ec['icon'] }} Email: {{ ucfirst($latestEmail->status) }}
                                                </span>
                                            @endif
                                            
                                            @if($result->tags->count() > 0)
                                                <div class="flex flex-wrap gap-1">
                                                    @foreach($result->tags as $tag)
                                                        <span 
                                                            class="inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-xs font-semibold text-white shadow-sm"
                                                            style="background: linear-gradient(135deg, {{ $tag->color }} 0%, {{ $tag->color }}dd 100%)"
                                                        >
                                                            {{ $tag->name }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            @endif
                                            
                                            <button 
                                                wire:click="openTagsModal({{ $result->id }})"
                                                class="inline-flex items-center gap-1 text-xs font-medium text-orange-600 hover:text-orange-700 cursor-pointer transition-colors"
                                            >
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                                                {{ $result->tags->count() > 0 ? 'Edit' : 'Add' }}
                                            </button>
                                        </div>
                                    </td>
                                    
                                    <!-- Actions Column (compact) -->
                                    <td class="px-4 py-4 align-top">
                                        <div class="flex flex-col gap-1.5">
                                            @if($isGenerating)
                                            <div class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-orange-50 border border-orange-200">
                                                <svg class="animate-spin h-3.5 w-3.5 text-orange-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                <span class="text-xs font-semibold text-orange-700">Generating</span>
                                            </div>
                                        @elseif($hasGeneratedEmail)
                                            <button 
                                                wire:click="viewEmail({{ $result->id }})"
                                                class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 text-xs font-semibold bg-gradient-to-r from-sky-500 to-sky-600 text-white rounded-lg hover:from-sky-600 hover:to-sky-700 hover:shadow active:scale-95 transition-all cursor-pointer"
                                            >
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                View Email
                                            </button>
                                        @elseif($hasEmail)
                                            <button 
                                                wire:click="generateSingleEmail({{ $result->id }})"
                                                wire:loading.attr="disabled"
                                                class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 text-xs font-semibold bg-gradient-to-r from-orange-500 to-orange-600 text-white rounded-lg hover:from-orange-600 hover:to-orange-700 hover:shadow active:scale-95 disabled:from-slate-300 disabled:to-slate-300 disabled:cursor-not-allowed transition-all cursor-pointer"
                                            >
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                                                <span wire:loading.remove wire:target="generateSingleEmail({{ $result->id }})">Generate</span>
                                                <span wire:loading wire:target="generateSingleEmail({{ $result->id }})">...</span>
                                            </button>
                                        @endif
                                            <button 
                                                wire:click="openNotesModal({{ $result->id }})"
                                                class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 text-xs font-semibold bg-white border border-slate-200 text-slate-700 rounded-lg hover:border-slate-300 hover:bg-slate-50 active:scale-95 transition-all cursor-pointer"
                                            >
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                {{ $result->notes ? 'Edit' : 'Add' }}
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <div class="px-6 py-4 border-t border-slate-100">
                    {{ $results->links() }}
                </div>
            @else
                <div class="py-16 text-center">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-100 mb-4">
                        <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    <p class="text-slate-600 text-sm font-medium">
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
                            <tr class="bg-slate-50 border-b border-slate-200">
                                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">Company Name</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">Industry</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">Domain</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">Location</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">Employees</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">Contacts</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($companies as $company)
                                @php
                                    $contactsCount = $leadRequest->leadResults()
                                        ->where('company_id', $company->id)
                                        ->count();
                                @endphp
                                <tr class="border-b border-slate-100 hover:bg-orange-50/30 transition-colors bg-white">
                                    <td class="px-6 py-4">
                                        <div class="font-semibold text-slate-900">{{ $company->name }}</div>
                                        @if($company->website)
                                            <a href="{{ $company->website }}" target="_blank" class="text-xs text-orange-600 hover:text-orange-700 font-medium cursor-pointer transition-colors no-underline">
                                                Visit Website →
                                            </a>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="text-sm text-slate-600">{{ $company->industry ?? '-' }}</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        @if($company->domain)
                                            <a href="https://{{ $company->domain }}" target="_blank" class="text-orange-600 hover:text-orange-700 font-medium text-sm cursor-pointer transition-colors no-underline">
                                                {{ $company->domain }}
                                            </a>
                                        @else
                                            <span class="text-slate-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        @if($company->city || $company->country)
                                            <span class="text-sm text-slate-600">
                                                {{ trim(($company->city ?? '') . ', ' . ($company->country ?? ''), ', ') }}
                                            </span>
                                        @else
                                            <span class="text-slate-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        @if($company->employees)
                                            <span class="text-sm font-medium text-slate-900">{{ number_format($company->employees) }}</span>
                                        @else
                                            <span class="text-slate-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        @if($contactsCount > 0)
                                            <span class="inline-flex rounded-full bg-orange-50 border border-orange-100 px-3 py-1.5 text-xs font-semibold text-orange-700">
                                                {{ $contactsCount }} {{ $contactsCount === 1 ? 'contact' : 'contacts' }}
                                            </span>
                                        @else
                                            <span class="text-slate-400">0</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="py-16 text-center">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-100 mb-4">
                        <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    </div>
                    <p class="text-slate-600 text-sm font-medium">No companies found yet.</p>
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
                    <button wire:click="closeGenerateModal" class="text-gray-400 hover:text-gray-600 cursor-pointer">
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
                        class="px-4 py-2 text-sm font-semibold bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 cursor-pointer"
                    >
                        Cancel
                    </button>
                    <button 
                        wire:click="startGenerating"
                        wire:loading.attr="disabled"
                        class="px-4 py-2 text-sm font-semibold bg-orange-500 text-white rounded-lg hover:bg-orange-600 disabled:bg-gray-300 cursor-pointer"
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
                    <button wire:click="closeQueueModal" class="text-gray-400 hover:text-gray-600 cursor-pointer">
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
                        class="px-4 py-2 text-sm font-semibold bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 cursor-pointer"
                    >
                        Cancel
                    </button>
                    <button 
                        wire:click="queueSelectedEmails"
                        wire:loading.attr="disabled"
                        class="px-4 py-2 text-sm font-semibold bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:bg-gray-300 cursor-pointer"
                    >
                        <span wire:loading.remove>Queue & Send</span>
                        <span wire:loading>Queueing...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Mass Send Confirmation Modal -->
    @if($showMassSendModal)
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" wire:click="closeMassSendModal">
            <div class="bg-white rounded-lg p-6 max-w-2xl w-full mx-4" wire:click.stop>
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-2xl font-bold text-gray-900">Mass Send Generated Emails</h2>
                    <button wire:click="closeMassSendModal" class="text-gray-400 hover:text-gray-600 cursor-pointer">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <p class="text-gray-700 mb-4">
                    Queue and send <strong>{{ $massSendCount }} generated email(s)</strong> for this campaign?
                    Jobs are automatically staggered to reduce rate-limit risk.
                </p>

                <div class="flex justify-end gap-3">
                    <button 
                        wire:click="closeMassSendModal"
                        class="px-4 py-2 text-sm font-semibold bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 cursor-pointer"
                    >
                        Cancel
                    </button>
                    <button 
                        wire:click="massSendGeneratedEmails"
                        wire:loading.attr="disabled"
                        class="px-4 py-2 text-sm font-semibold bg-teal-600 text-white rounded-lg hover:bg-teal-700 disabled:bg-gray-300 cursor-pointer"
                    >
                        <span wire:loading.remove wire:target="massSendGeneratedEmails">Mass Send</span>
                        <span wire:loading wire:target="massSendGeneratedEmails">Queueing...</span>
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
                    <button wire:click="closeQueuedEmailsModal" class="text-gray-400 hover:text-gray-600 cursor-pointer">
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
                    <button wire:click="closeEmailModal" class="text-gray-400 hover:text-gray-600 cursor-pointer">
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
                        class="px-4 py-2 text-sm font-semibold bg-orange-500 text-white rounded-lg hover:bg-orange-600 disabled:bg-gray-300 cursor-pointer"
                    >
                        <span wire:loading.remove>Regenerate Email</span>
                        <span wire:loading>Regenerating...</span>
                    </button>
                    <div class="flex gap-3">
                        <button 
                            wire:click="closeEmailModal"
                            class="px-4 py-2 text-sm font-semibold bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 cursor-pointer"
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
                    <button wire:click="closeBulkStatusModal" class="text-gray-400 hover:text-gray-600 cursor-pointer">
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
                        class="px-4 py-2 text-sm font-semibold bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 cursor-pointer"
                    >
                        Cancel
                    </button>
                    <button 
                        wire:click="updateBulkStatus"
                        wire:loading.attr="disabled"
                        class="px-4 py-2 text-sm font-semibold bg-purple-600 text-white rounded-lg hover:bg-purple-700 disabled:bg-gray-300 cursor-pointer"
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
                    <button wire:click="closeNotesModal" class="text-gray-400 hover:text-gray-600 cursor-pointer">
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
                        class="px-4 py-2 text-sm font-semibold bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 cursor-pointer"
                    >
                        Cancel
                    </button>
                    <button 
                        wire:click="saveNotes"
                        class="px-4 py-2 text-sm font-semibold bg-blue-600 text-white rounded-lg hover:bg-blue-700 cursor-pointer"
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
                    <button wire:click="closeTagsModal" class="text-gray-400 hover:text-gray-600 cursor-pointer">
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
                            class="px-4 py-2 text-sm font-semibold bg-orange-500 text-white rounded-lg hover:bg-orange-600 cursor-pointer"
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
                        class="px-4 py-2 text-sm font-semibold bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 cursor-pointer"
                    >
                        Cancel
                    </button>
                    <button 
                        wire:click="saveTags"
                        class="px-4 py-2 text-sm font-semibold bg-blue-600 text-white rounded-lg hover:bg-blue-700 cursor-pointer"
                    >
                        Save Tags
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
