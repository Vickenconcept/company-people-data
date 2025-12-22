<div class="space-y-6">
    <div>
        <flux:heading>Create New Lead Request</flux:heading>
        <flux:subheading>Enter a reference company to find similar companies and their key contacts</flux:subheading>
    </div>

    @if(session()->has('message'))
        <div class="rounded-lg bg-green-50 border border-green-200 p-4 text-green-800">
            {{ session('message') }}
        </div>
    @endif

    <div class="rounded-lg border-2 border-gray-300 bg-white p-6 shadow-sm">
        <form wire:submit="create" class="space-y-6">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Reference Company Name *</label>
                <input 
                    wire:model="reference_company_name" 
                    type="text"
                    placeholder="e.g., Nike" 
                    required
                    class="w-full rounded-lg border-2 border-gray-300 px-3 py-2 focus:border-blue-500 focus:outline-none text-gray-900"
                />
                @error('reference_company_name') <flux:text class="text-red-600">{{ $message }}</flux:text> @enderror
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Company Website URL</label>
                <input 
                    wire:model="reference_company_url" 
                    type="url" 
                    placeholder="https://nike.com"
                    class="w-full rounded-lg border-2 border-gray-300 px-3 py-2 focus:border-blue-500 focus:outline-none text-gray-900"
                />
                @error('reference_company_url') <flux:text class="text-red-600">{{ $message }}</flux:text> @enderror
                <flux:text class="text-sm text-gray-500">Optional: Provide the company website for better analysis</flux:text>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Target Count *</label>
                <input 
                    wire:model="target_count" 
                    type="number" 
                    min="1" 
                    max="100" 
                    required
                    class="w-full rounded-lg border-2 border-gray-300 px-3 py-2 focus:border-blue-500 focus:outline-none text-gray-900"
                />
                @error('target_count') <flux:text class="text-red-600">{{ $message }}</flux:text> @enderror
                <flux:text class="text-sm text-gray-500">Number of similar companies to find (1-100)</flux:text>
            </div>

            <div>
                <flux:text class="mb-2 block font-medium">Target Job Titles *</flux:text>
                <div class="space-y-2">
                    <div class="flex gap-2">
                        <input 
                            wire:model="new_job_title" 
                            placeholder="e.g., CTO" 
                            class="flex-1 rounded-lg border-2 border-gray-300 px-3 py-2 focus:border-blue-500 focus:outline-none text-gray-900"
                        />
                        <button 
                            type="button" 
                            wire:click="addJobTitle"
                            class="px-4 py-2 rounded-lg border-2 border-gray-300 bg-white text-gray-700 hover:bg-gray-50 font-medium"
                        >
                            Add
                        </button>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @foreach($target_job_titles as $title)
                            <span class="inline-flex items-center gap-1 rounded-full bg-blue-100 px-3 py-1 text-sm font-medium text-blue-800">
                                {{ $title }}
                                <button type="button" wire:click="removeJobTitle('{{ $title }}')" class="text-blue-700 hover:text-blue-900 font-bold">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </span>
                        @endforeach
                    </div>
                </div>
                @error('target_job_titles') <flux:text class="text-red-600">{{ $message }}</flux:text> @enderror
                <flux:text class="text-sm text-gray-500">Job titles to search for (e.g., CEO, CFO, CTO)</flux:text>
            </div>

            <div class="flex gap-4">
                <button 
                    type="submit"
                    class="px-6 py-2 rounded-lg bg-blue-600 text-white font-semibold hover:bg-blue-700 border-2 border-blue-600"
                    style="color: #ffffff !important;"
                >
                    Create Lead Request
                </button>
                <a 
                    href="{{ route('leads.dashboard') }}" 
                    wire:navigate
                    class="px-6 py-2 rounded-lg border-2 border-gray-300 bg-white text-gray-700 font-semibold hover:bg-gray-50"
                >
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
