<div class="space-y-6">
    <div>
        <flux:heading>API Keys Management</flux:heading>
        <flux:subheading>Manage your API keys for OpenAI, ScraperAPI, Apollo, and Hunter</flux:subheading>
    </div>

    @if(session()->has('message'))
        <div class="rounded-lg bg-green-50 border border-green-200 p-4 text-green-800">
            {{ session('message') }}
        </div>
    @endif

    <!-- Add/Edit Form -->
    <div class="rounded-lg border-2 border-gray-300 bg-white p-6 shadow-sm">
        <flux:heading size="lg" class="mb-4">
            {{ $editing_id ? 'Edit API Key' : 'Add New API Key' }}
        </flux:heading>
        
        <form wire:submit="save" class="space-y-4">
            <div>
                <flux:text class="mb-2 block font-medium">Service *</flux:text>
                <select wire:model="selected_service" class="w-full rounded-lg border-2 border-gray-300 px-3 py-2 focus:border-orange-500 focus:outline-none bg-white text-gray-900 font-medium">
                    @foreach($services as $key => $name)
                        <option value="{{ $key }}">{{ $name }}</option>
                    @endforeach
                </select>
                @error('selected_service') <flux:text class="text-red-600">{{ $message }}</flux:text> @enderror
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">API Key *</label>
                <input 
                    wire:model="api_key" 
                    type="password" 
                    placeholder="Enter your API key" 
                    required
                    class="w-full rounded-lg border-2 border-gray-300 px-3 py-2 focus:border-orange-500 focus:outline-none text-gray-900"
                />
            @error('api_key') <flux:text class="text-red-600">{{ $message }}</flux:text> @enderror
            <flux:text class="text-sm text-gray-500">Your API key will be securely stored</flux:text>

            <flux:checkbox wire:model="is_active" label="Active" />
            <flux:text class="text-sm text-gray-500">Inactive keys won't be used</flux:text>

            <div class="flex gap-4">
                <button 
                    type="submit"
                    class="px-6 py-2 rounded-lg bg-orange-500 text-white font-semibold hover:bg-orange-600 border-2 border-orange-500"
                    style="color: #ffffff !important;"
                >
                    {{ $editing_id ? 'Update' : 'Save' }} API Key
                </button>
                @if($editing_id)
                    <button 
                        type="button" 
                        wire:click="cancelEdit"
                        class="px-6 py-2 rounded-lg border-2 border-gray-300 bg-white text-gray-700 font-semibold hover:bg-gray-50"
                    >
                        Cancel
                    </button>
                @endif
            </div>
        </form>
    </div>

    <!-- Existing API Keys -->
    <div class="rounded-lg border-2 border-gray-300 bg-white p-6 shadow-sm">
        <flux:heading size="lg" class="mb-4">Your API Keys</flux:heading>
        
        @if($apiKeys->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="px-4 py-3 text-left text-sm font-semibold">Service</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold">Status</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold">Last Updated</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($apiKeys as $apiKey)
                            <tr class="border-b border-gray-200 hover:bg-orange-50 transition-colors">
                                <td class="px-4 py-3">
                                    <div class="font-medium">{{ $services[$apiKey->service] ?? $apiKey->service }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    @if($apiKey->is_active)
                                        <span class="inline-flex rounded-full bg-green-200 px-2 py-1 text-xs font-semibold text-green-900">
                                            Active
                                        </span>
                                    @else
                                        <span class="inline-flex rounded-full bg-gray-200 px-2 py-1 text-xs font-semibold text-gray-900">
                                            Inactive
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600">
                                    {{ $apiKey->updated_at->diffForHumans() }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex gap-2">
                                        <button 
                                            wire:click="edit({{ $apiKey->id }})"
                                            class="px-3 py-1 rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 text-sm font-medium"
                                        >
                                            Edit
                                        </button>
                                        <button 
                                            wire:click="delete({{ $apiKey->id }})" 
                                            wire:confirm="Are you sure you want to delete this API key?"
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
        @else
            <div class="py-12 text-center">
                <p class="text-gray-600">No API keys configured yet. Add your first API key above.</p>
            </div>
        @endif
    </div>
</div>
