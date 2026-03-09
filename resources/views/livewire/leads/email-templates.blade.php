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
                <h1 class="text-2xl font-bold text-slate-900 leading-tight">Email Templates</h1>
                <p class="text-sm text-slate-500 mt-1">Manage your email templates for lead generation</p>
            </div>
        </div>
        <button 
            wire:click="openModal"
            class="px-6 py-2.5 rounded-full bg-gradient-to-r from-orange-500 to-orange-600 text-white font-semibold hover:from-orange-600 hover:to-orange-700 transition-all shadow-sm cursor-pointer no-underline"
        >
            Create Template
        </button>
    </div>

    @if($message)
        <div class="rounded-xl p-4 {{ $messageType === 'success' ? 'bg-emerald-50 text-emerald-800 border border-emerald-100' : 'bg-red-50 text-red-800 border border-red-100' }} flex items-center gap-3">
            @if($messageType === 'success')
                <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            @else
                <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
            @endif
            <span class="font-medium">{{ $message }}</span>
        </div>
    @endif

    @if($templates->count() > 0)
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            @foreach($templates as $template)
                <div class="group rounded-2xl border border-slate-100 bg-white shadow-sm hover:shadow-md transition-all duration-200 overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    <h3 class="text-lg font-bold text-slate-900">
                                        {{ $template->name }}
                                    </h3>
                                    @if($template->is_default)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold bg-gradient-to-r from-orange-500 to-orange-600 text-white rounded-full shadow-sm">
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                            Default
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        
                        <div class="space-y-3">
                            <div>
                                <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Subject</div>
                                <div class="text-sm text-slate-900 font-medium">@php echo \Illuminate\Support\Str::limit($template->subject, 60); @endphp</div>
                            </div>
                            
                            <div>
                                <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Body Preview</div>
                                <div class="text-xs text-slate-600 leading-relaxed">@php echo \Illuminate\Support\Str::limit(strip_tags($template->body), 100); @endphp</div>
                            </div>

                            @if($template->custom_context)
                                <div>
                                    <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Context</div>
                                    <div class="text-xs text-slate-500 italic">@php echo \Illuminate\Support\Str::limit($template->custom_context, 80); @endphp</div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="flex gap-2 p-4 bg-slate-50 border-t border-slate-100">
                        <button 
                            wire:click="openModal({{ $template->id }})"
                            class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 text-sm font-semibold bg-slate-700 text-white rounded-lg hover:bg-slate-800 transition-colors cursor-pointer"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            Edit
                        </button>
                        <button 
                            wire:click="delete({{ $template->id }})"
                            wire:confirm="Are you sure you want to delete this template?"
                            class="inline-flex items-center justify-center px-3 py-2 text-sm font-semibold bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors cursor-pointer"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="py-16 text-center rounded-2xl border border-slate-100 bg-white shadow-sm">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-orange-100 mb-4">
                <svg class="w-8 h-8 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            </div>
            <p class="text-slate-600 mb-4 font-medium">No email templates yet.</p>
            <button 
                wire:click="openModal"
                class="px-6 py-2.5 rounded-full bg-gradient-to-r from-orange-500 to-orange-600 text-white font-semibold hover:from-orange-600 hover:to-orange-700 transition-all shadow-sm cursor-pointer"
            >
                Create Your First Template
            </button>
        </div>
    @endif

    <!-- Create/Edit Modal -->
    @if($showModal)
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" wire:click="closeModal">
            <div class="bg-white rounded-2xl max-w-4xl w-full max-h-[90vh] flex flex-col shadow-2xl" wire:click.stop>
                <!-- Modal Header - Fixed -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 flex-shrink-0">
                    <h2 class="text-xl font-bold text-slate-900">
                        {{ $editingId ? 'Edit Template' : 'Create Template' }}
                    </h2>
                    <button wire:click="closeModal" class="text-slate-400 hover:text-slate-600 cursor-pointer transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Modal Body - Scrollable -->
                <div class="overflow-y-auto flex-1 px-6 py-4">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Template Name *</label>
                            <input 
                                type="text"
                                wire:model="name"
                                class="w-full border border-slate-200 rounded-lg px-3 py-2.5 focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 focus:outline-none transition-colors"
                                placeholder="e.g., Sales Outreach Template"
                            >
                            @error('name') <span class="text-red-600 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Subject *</label>
                            <input 
                                type="text"
                                wire:model="subject"
                                class="w-full border border-slate-200 rounded-lg px-3 py-2.5 focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 focus:outline-none transition-colors"
                                placeholder="Email subject line"
                            >
                            @error('subject') <span class="text-red-600 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Body *</label>
                            <textarea 
                                wire:model="body"
                                rows="8"
                                class="w-full border border-slate-200 rounded-lg px-3 py-2.5 focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 focus:outline-none transition-colors resize-none"
                                placeholder="Email body content"
                            ></textarea>
                            @error('body') <span class="text-red-600 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Custom Context (Optional)</label>
                            <textarea 
                                wire:model="custom_context"
                                rows="3"
                                class="w-full border border-slate-200 rounded-lg px-3 py-2.5 focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 focus:outline-none transition-colors resize-none"
                                placeholder="Additional context to help AI personalize the email"
                            ></textarea>
                            <p class="text-xs text-slate-500 mt-1.5">This context will be used by AI when generating emails with this template.</p>
                        </div>

                        <div class="pt-2">
                            <label class="flex items-center gap-2.5 cursor-pointer group">
                                <input 
                                    type="checkbox"
                                    wire:model="is_default"
                                    class="w-4 h-4 rounded border-slate-300 text-orange-600 focus:ring-2 focus:ring-orange-500/20 cursor-pointer"
                                >
                                <span class="text-sm font-semibold text-slate-700 group-hover:text-slate-900 transition-colors">Set as default template</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer - Fixed -->
                <div class="flex justify-end gap-3 px-6 py-4 border-t border-slate-100 bg-slate-50 flex-shrink-0 rounded-b-2xl">
                    <button 
                        wire:click="closeModal"
                        class="px-4 py-2 text-sm font-semibold bg-white border border-slate-200 text-slate-700 rounded-lg hover:bg-slate-50 hover:border-slate-300 transition-colors cursor-pointer"
                    >
                        Cancel
                    </button>
                    <button 
                        wire:click="save"
                        class="px-4 py-2 text-sm font-semibold bg-gradient-to-r from-orange-500 to-orange-600 text-white rounded-lg hover:from-orange-600 hover:to-orange-700 transition-all shadow-sm cursor-pointer"
                    >
                        {{ $editingId ? 'Update' : 'Create' }} Template
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
