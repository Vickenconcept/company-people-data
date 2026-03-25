@auth
    <div
        class="relative hidden md:block"
        wire:click.outside="closePanel"
    >
        <div class="relative">
            <input
                type="search"
                autocomplete="off"
                placeholder="{{ __('Search leads, companies...') }}"
                class="w-64 rounded-full border border-slate-200 bg-white px-4 py-2 pr-9 text-sm text-slate-700 shadow-sm focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-400"
                wire:model.debounce.300ms="q"
                x-on:focus="$wire.openPanel()"
            >
            <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-slate-400">
                <flux:icon name="magnifying-glass" class="size-4" />
            </span>
        </div>

        @if ($open)
            <div
                class="absolute right-0 z-50 mt-2 w-[min(22rem,calc(100vw-2rem))] overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg ring-1 ring-black/5"
                role="listbox"
                aria-label="{{ __('Search results') }}"
            >
                <div class="max-h-72 overflow-y-auto overscroll-contain">
                    @forelse ($items as $row)
                        <a
                            href="{{ $row['url'] }}"
                            wire:navigate
                            wire:click="closePanel"
                            wire:key="{{ $row['key'] }}"
                            class="flex flex-col gap-0.5 border-b border-slate-100 px-4 py-3 text-left transition-colors last:border-b-0 hover:bg-orange-50 focus:bg-orange-50 focus:outline-none"
                            role="option"
                        >
                            <span class="text-sm font-semibold text-slate-900">{{ $row['title'] }}</span>
                            <span class="text-xs text-slate-500">{{ $row['meta'] }}</span>
                        </a>
                    @empty
                        <p class="px-4 py-6 text-center text-sm text-slate-500">
                            {{ trim($q) === '' ? __('Nothing to show yet.') : __('No matches.') }}
                        </p>
                    @endforelse
                </div>

                @if ($hasMore && trim($q) !== '')
                    <div class="border-t border-slate-100 bg-slate-50 px-3 py-2">
                        <button
                            type="button"
                            wire:click="loadMore"
                            wire:loading.attr="disabled"
                            class="w-full rounded-lg py-2 text-center text-xs font-semibold text-orange-600 hover:text-orange-700 disabled:opacity-50"
                        >
                            <span wire:loading.remove wire:target="loadMore">{{ __('Load more') }}</span>
                            <span wire:loading wire:target="loadMore">{{ __('Loading…') }}</span>
                        </button>
                    </div>
                @endif
            </div>
        @endif
    </div>
@endauth
