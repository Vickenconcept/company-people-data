<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading>Email Templates</flux:heading>
            <flux:subheading>Manage your email templates for lead generation</flux:subheading>
        </div>
        <button 
            wire:click="openModal"
            class="px-6 py-2 rounded-lg bg-orange-500 text-white font-semibold hover:bg-orange-600 border-2 border-orange-500"
            style="color: #ffffff !important;"
        >
            Create Template
        </button>
    </div>

    @if($message)
        <div class="rounded-lg p-4 {{ $messageType === 'success' ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-red-50 text-red-800 border border-red-200' }}">
            {{ $message }}
        </div>
    @endif

    @if($templates->count() > 0)
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            @foreach($templates as $template)
                <div class="rounded-lg border-2 border-gray-300 bg-white p-6 shadow-sm hover:shadow-md transition-shadow">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex-1">
                            <h3 class="text-lg font-bold text-gray-900">
                                {{ $template->name }}
                                @if($template->is_default)
                                    <span class="ml-2 text-xs font-semibold bg-orange-500 text-white px-2 py-0.5 rounded">Default</span>
                                @endif
                            </h3>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <div class="text-sm font-semibold text-gray-600 mb-1">Subject:</div>
                        <div class="text-sm text-gray-900">@php echo \Illuminate\Support\Str::limit($template->subject, 60); @endphp</div>
                    </div>
                    
                    <div class="mb-4">
                        <div class="text-sm font-semibold text-gray-600 mb-1">Body Preview:</div>
                        <div class="text-sm text-gray-700">@php echo \Illuminate\Support\Str::limit(strip_tags($template->body), 100); @endphp</div>
                    </div>

                    @if($template->custom_context)
                        <div class="mb-4">
                            <div class="text-sm font-semibold text-gray-600 mb-1">Context:</div>
                            <div class="text-xs text-gray-600">@php echo \Illuminate\Support\Str::limit($template->custom_context, 80); @endphp</div>
                        </div>
                    @endif

                    <div class="flex gap-2 mt-4">
                        <button 
                            wire:click="openModal({{ $template->id }})"
                            class="flex-1 px-3 py-2 text-sm font-semibold bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                        >
                            Edit
                        </button>
                        <button 
                            wire:click="delete({{ $template->id }})"
                            wire:confirm="Are you sure you want to delete this template?"
                            class="px-3 py-2 text-sm font-semibold bg-red-600 text-white rounded-lg hover:bg-red-700"
                        >
                            Delete
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="py-12 text-center rounded-lg border-2 border-gray-300 bg-white">
            <p class="text-gray-600 mb-4">No email templates yet.</p>
            <button 
                wire:click="openModal"
                class="px-6 py-2 rounded-lg bg-orange-500 text-white font-semibold hover:bg-orange-600"
            >
                Create Your First Template
            </button>
        </div>
    @endif

    <!-- Create/Edit Modal -->
    @if($showModal)
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" wire:click="closeModal">
            <div class="bg-white rounded-lg p-6 max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto" wire:click.stop>
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-2xl font-bold text-gray-900">
                        {{ $editingId ? 'Edit Template' : 'Create Template' }}
                    </h2>
                    <button wire:click="closeModal" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Template Name *</label>
                        <input 
                            type="text"
                            wire:model="name"
                            class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 focus:border-orange-500 focus:outline-none"
                            placeholder="e.g., Sales Outreach Template"
                        >
                        @error('name') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Subject *</label>
                        <input 
                            type="text"
                            wire:model="subject"
                            class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 focus:border-orange-500 focus:outline-none"
                            placeholder="Email subject line"
                        >
                        @error('subject') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Body *</label>
                        <textarea 
                            wire:model="body"
                            rows="10"
                            class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 focus:border-orange-500 focus:outline-none"
                            placeholder="Email body content"
                        ></textarea>
                        @error('body') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Custom Context (Optional)</label>
                        <textarea 
                            wire:model="custom_context"
                            rows="4"
                            class="w-full border-2 border-gray-300 rounded-lg px-3 py-2 focus:border-orange-500 focus:outline-none"
                            placeholder="Additional context to help AI personalize the email"
                        ></textarea>
                        <p class="text-xs text-gray-500 mt-1">This context will be used by AI when generating emails with this template.</p>
                    </div>

                    <div>
                        <label class="flex items-center gap-2">
                            <input 
                                type="checkbox"
                                wire:model="is_default"
                                class="rounded border-gray-300"
                            >
                            <span class="text-sm font-semibold text-gray-700">Set as default template</span>
                        </label>
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-6">
                    <button 
                        wire:click="closeModal"
                        class="px-4 py-2 text-sm font-semibold bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200"
                    >
                        Cancel
                    </button>
                    <button 
                        wire:click="save"
                        class="px-4 py-2 text-sm font-semibold bg-orange-500 text-white rounded-lg hover:bg-orange-600"
                    >
                        {{ $editingId ? 'Update' : 'Create' }} Template
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
