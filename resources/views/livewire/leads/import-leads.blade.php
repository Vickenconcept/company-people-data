<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="flex items-start gap-3">
            <a
                href="{{ route('leads.dashboard') }}"
                wire:navigate
                class="flex items-center justify-center w-10 h-10 rounded-2xl bg-white border border-violet-100 text-slate-600 hover:border-violet-300 hover:bg-violet-50 hover:text-violet-700 transition-all shadow-sm cursor-pointer no-underline"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div>
                <h2 class="text-2xl font-bold text-slate-900 leading-tight">Import Leads</h2>
                <p class="mt-1 text-sm text-slate-500">Upload your CSV file and create lead records in one step.</p>
            </div>
        </div>
        <span class="inline-flex items-center rounded-full bg-violet-100 px-3 py-1 text-xs font-semibold text-violet-700">
            Step 1 · Upload CSV
        </span>
    </div>

    <div class="rounded-3xl border border-violet-100/80 bg-white p-6 shadow-sm">
        <h3 class="text-lg font-semibold text-slate-900 mb-4">CSV Format Requirements</h3>
        <div class="mb-6 space-y-3 text-sm text-slate-700">
            <p>Your CSV file should contain the following columns (at minimum):</p>
            <ul class="list-disc list-inside ml-4 space-y-1">
                <li><strong>Company Name</strong> - Name of the company</li>
                <li><strong>Contact Name</strong> - Full name of the contact person</li>
                <li><strong>Email</strong> - Email address of the contact</li>
                <li><strong>Title</strong> - Job title (optional)</li>
                <li><strong>Phone</strong> - Phone number (optional)</li>
                <li><strong>Industry</strong> - Company industry (optional)</li>
                <li><strong>Website</strong> - Company website (optional)</li>
            </ul>
            <p class="mt-1 text-xs text-slate-500">The CSV file should have a header row. Column names are case-insensitive and spaces will be converted to underscores.</p>
            <div class="mt-3">
                <a
                    href="{{ asset('templates/leads_import_template.csv') }}"
                    download
                    class="inline-flex items-center gap-2 rounded-2xl border border-violet-100 bg-white px-4 py-2 text-xs font-medium text-slate-700 shadow-sm hover:bg-violet-50/40"
                >
                    <flux:icon name="arrow-down-tray" class="size-4" />
                    <span>Download CSV template</span>
                </a>
            </div>
        </div>

        @if($message)
            <div class="mb-4 rounded-2xl p-4 {{ $messageType === 'success' ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-red-50 text-red-800 border border-red-200' }}">
                {{ $message }}
            </div>
        @endif

        <form wire:submit="import">
            <div class="mb-4">
                <label class="block text-sm font-semibold text-slate-700 mb-2">
                    Select CSV File
                </label>
                <input 
                    type="file"
                    wire:model="csvFile"
                    accept=".csv,.txt"
                    class="dropify"
                    data-allowed-file-extensions="csv txt"
                    data-max-file-size="10M"
                >
                @error('csvFile') 
                    <span class="text-red-600 text-xs mt-1">{{ $message }}</span> 
                @enderror
                @if($csvFile)
                    <p class="text-sm text-slate-600 mt-2">Selected: {{ $csvFile->getClientOriginalName() }}</p>
                @endif
            </div>

            <div class="flex justify-end gap-3">
                <a
                    href="{{ route('leads.dashboard') }}" 
                    wire:navigate
                    class="px-4 py-2 text-sm font-semibold bg-white border border-violet-100 text-slate-700 rounded-2xl hover:bg-violet-50/40"
                >
                    Cancel
                </a>
                <button 
                    type="submit"
                    wire:loading.attr="disabled"
                    class="px-4 py-2 text-sm font-semibold bg-gradient-to-r from-[#8f47f2] to-[#5d30dc] text-white rounded-2xl hover:opacity-95 disabled:bg-gray-300"
                >
                    <span wire:loading.remove>Import Leads</span>
                    <span wire:loading>Importing...</span>
                </button>
            </div>
        </form>

        @if($isImporting)
            <div class="mt-4 p-4 bg-violet-50 border border-violet-200 rounded-2xl">
                <div class="flex items-center gap-2">
                    <svg class="animate-spin h-5 w-5 text-violet-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="text-violet-800">Importing leads... Please wait.</span>
                </div>
            </div>
        @endif

    </div>
</div>
