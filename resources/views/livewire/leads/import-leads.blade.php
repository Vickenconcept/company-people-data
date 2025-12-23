<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading>Import Leads</flux:heading>
            <flux:subheading>Import leads from a CSV file</flux:subheading>
        </div>
        <a 
            href="{{ route('leads.dashboard') }}" 
            wire:navigate
            class="px-4 py-2 rounded-lg bg-gray-100 text-gray-700 font-semibold hover:bg-gray-200"
        >
            Back to Dashboard
        </a>
    </div>

    <div class="rounded-lg border-2 border-gray-300 bg-white p-6 shadow-sm">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">CSV Format Requirements</h3>
        <div class="mb-6 space-y-2 text-sm text-gray-700">
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
            <p class="mt-4 text-xs text-gray-600">The CSV file should have a header row. Column names are case-insensitive and spaces will be converted to underscores.</p>
        </div>

        @if($message)
            <div class="mb-4 rounded-lg p-4 {{ $messageType === 'success' ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-red-50 text-red-800 border border-red-200' }}">
                {{ $message }}
            </div>
        @endif

        <form wire:submit="import">
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    Select CSV File
                </label>
                <input 
                    type="file"
                    wire:model="csvFile"
                    accept=".csv,.txt"
                    class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 focus:border-orange-500 focus:outline-none"
                >
                @error('csvFile') 
                    <span class="text-red-600 text-xs mt-1">{{ $message }}</span> 
                @enderror
                @if($csvFile)
                    <p class="text-sm text-gray-600 mt-2">Selected: {{ $csvFile->getClientOriginalName() }}</p>
                @endif
            </div>

            <div class="flex justify-end gap-3">
                <a 
                    href="{{ route('leads.dashboard') }}" 
                    wire:navigate
                    class="px-4 py-2 text-sm font-semibold bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200"
                >
                    Cancel
                </a>
                <button 
                    type="submit"
                    wire:loading.attr="disabled"
                    class="px-4 py-2 text-sm font-semibold bg-orange-500 text-white rounded-lg hover:bg-orange-600 disabled:bg-gray-300"
                >
                    <span wire:loading.remove>Import Leads</span>
                    <span wire:loading>Importing...</span>
                </button>
            </div>
        </form>

        @if($isImporting)
            <div class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <div class="flex items-center gap-2">
                    <svg class="animate-spin h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="text-blue-800">Importing leads... Please wait.</span>
                </div>
            </div>
        @endif
    </div>
</div>

