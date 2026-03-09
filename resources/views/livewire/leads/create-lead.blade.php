<div class="space-y-6">
    <!-- Page header -->
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
                <h2 class="text-2xl font-bold text-slate-900 leading-tight">
                    {{ __('Create New Lead Request') }}
                </h2>
                <p class="mt-1 text-sm text-slate-500">
                    {{ __('Start from a reference company and let the engine find lookalike accounts and key contacts for you.') }}
                </p>
            </div>
        </div>
        <div class="flex items-center gap-2 text-xs text-slate-500">
            <span class="rounded-full bg-orange-50 px-3 py-1 font-medium text-orange-600">
                {{ __('Step 1 · Configure request') }}
            </span>
        </div>
    </div>

    @if(session()->has('message'))
        <div class="rounded-lg bg-green-50 border border-green-200 p-4 text-green-800">
            {{ session('message') }}
        </div>
    @endif

    <div class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
        <form wire:submit="create" class="space-y-6">
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-600 mb-1">Reference Company Name *</label>
                <input 
                    wire:model="reference_company_name" 
                    type="text"
                    placeholder="e.g., Nike"
                    required
                    class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-orange-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-orange-400"
                />
                @error('reference_company_name') <flux:text class="text-red-600">{{ $message }}</flux:text> @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-600 mb-1">Company Website URL</label>
                <input 
                    wire:model="reference_company_url" 
                    type="url" 
                    placeholder="https://nike.com"
                    class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-400"
                />
                @error('reference_company_url') <flux:text class="text-red-600">{{ $message }}</flux:text> @enderror
                <flux:text class="text-xs text-slate-500">Optional: Provide the company website for better analysis.</flux:text>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-600 mb-1">Target Count *</label>
                <input 
                    wire:model="target_count" 
                    type="number"
                    min="1"
                    max="100"
                    required
                    class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-400"
                />
                    @error('target_count') <flux:text class="text-red-600">{{ $message }}</flux:text> @enderror
                    <flux:text class="text-xs text-slate-500">Number of similar companies to find (1–100).</flux:text>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-600 mb-1">Country (Optional)</label>
                    <input 
                        wire:model="country" 
                        type="text"
                        placeholder="e.g., NG, US, GB" 
                        maxlength="2"
                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 uppercase focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-400"
                    />
                    @error('country') <flux:text class="text-red-600">{{ $message }}</flux:text> @enderror
                    <flux:text class="text-xs text-slate-500">Optional: ISO 2-letter code (e.g., NG, US, GB).</flux:text>
                </div>
            </div>

            <div>
                <flux:text class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-600">Target Job Titles *</flux:text>
                <div class="space-y-2">
                    <div class="flex gap-2">
                        <input 
                            wire:model="new_job_title"
                            placeholder="e.g., CTO"
                            class="flex-1 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-400"
                        />
                        <button 
                            type="button" 
                            wire:click="addJobTitle"
                            class="px-4 py-2 rounded-full border border-slate-200 bg-white text-xs font-medium text-slate-700 hover:bg-slate-50"
                        >
                            Add
                        </button>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @foreach($target_job_titles as $title)
                            <span class="inline-flex items-center gap-1 rounded-full bg-orange-50 px-3 py-1 text-xs font-medium text-orange-700">
                                {{ $title }}
                                <button type="button" wire:click="removeJobTitle('{{ $title }}')" class="text-orange-600 hover:text-orange-800 font-bold">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </span>
                        @endforeach
                    </div>
                </div>
                @error('target_job_titles') <flux:text class="text-red-600">{{ $message }}</flux:text> @enderror
                <flux:text class="text-xs text-slate-500">Job titles to search for (e.g., CEO, CFO, CTO).</flux:text>
            </div>

            <div class="flex flex-wrap gap-3">
                <button 
                    type="submit"
                    class="inline-flex items-center gap-2 rounded-full bg-orange-500 px-6 py-2 text-sm font-semibold text-white shadow-md shadow-orange-500/30 hover:bg-orange-600"
                >
                    <flux:icon name="sparkles" class="size-4" />
                    <span>{{ __('Create Lead Request') }}</span>
                </button>
                <a 
                    href="{{ route('leads.dashboard') }}" 
                    wire:navigate
                    class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-5 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                >
                    {{ __('Cancel') }}
                </a>
            </div>
        </form>
    </div>
</div>
