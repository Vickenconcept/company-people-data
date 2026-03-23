t<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-[#f5f6fb] text-slate-900 antialiased">
        <div class="min-h-screen flex">
            <!-- Left sidebar (icon-first, dark) -->
            <flux:sidebar
                sticky
                stashable
                class="hidden lg:flex w-20 flex-col items-center gap-6 border-none bg-gray-800 text-white py-6 shadow-2xl"
            >
                <a href="{{ route('dashboard') }}" class="flex items-center justify-center mb-6" wire:navigate>
                    <x-app-logo-icon class="size-9 text-white" />
                </a>

                <flux:navlist class="!space-y-4">
                    <flux:navlist.item
                        icon="layout-grid"
                        :href="route('dashboard')"
                        :current="request()->routeIs('dashboard') || request()->routeIs('leads.dashboard')"
                        wire:navigate
                        class="justify-center rounded-xl border-0 data-[current=true]:bg-orange-500 data-[current=true]:text-white hover:bg-orange-400/90 hover:text-white"
                    />

                    <flux:navlist.item
                        icon="book-open-text"
                        :href="route('leads.all')"
                        :current="request()->routeIs('leads.all')"
                        wire:navigate
                        class="justify-center rounded-xl border-0 hover:bg-gray-700 hover:text-white data-[current=true]:bg-gray-700 data-[current=true]:text-white"
                    />

                    <flux:navlist.item
                        icon="plus"
                        :href="route('leads.create')"
                        :current="request()->routeIs('leads.create')"
                        wire:navigate
                        class="justify-center rounded-xl border-0 hover:bg-gray-700 hover:text-white data-[current=true]:bg-gray-700 data-[current=true]:text-white"
                    />

                    <flux:navlist.item
                        icon="key"
                        :href="route('api-keys')"
                        :current="request()->routeIs('api-keys')"
                        wire:navigate
                        class="justify-center rounded-xl border-0 !bg-black !text-white !hover:bg-white !hover:text-black"
                    />
                </flux:navlist>

                <flux:spacer />

                <flux:dropdown position="top" align="center">
                    <flux:profile
                        class="cursor-pointer"
                        :initials="auth()->user()->initials()"
                    />

                    <flux:menu class="w-56">
                        <flux:menu.radio.group>
                            <div class="p-2 text-sm font-normal">
                                <div class="flex items-center gap-2">
                                    <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                        <span class="flex h-full w-full items-center justify-center rounded-lg bg-orange-100 text-orange-700 font-semibold">
                                            {{ auth()->user()->initials() }}
                                        </span>
                                    </span>
                                    <div class="grid flex-1 text-start text-sm leading-tight">
                                        <span class="truncate font-semibold text-slate-900">
                                            {{ auth()->user()->name }}
                                        </span>
                                        <span class="truncate text-xs text-slate-500">
                                            {{ auth()->user()->email }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </flux:menu.radio.group>

                        <flux:menu.separator />

                        <flux:menu.radio.group>
                            <flux:menu.item :href="route('settings.profile')" icon="cog" wire:navigate>
                                {{ __('Settings') }}
                            </flux:menu.item>
                        </flux:menu.radio.group>

                        <flux:menu.separator />

                        <form method="POST" action="{{ route('logout') }}" class="w-full">
                            @csrf
                            <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                                {{ __('Log Out') }}
                            </flux:menu.item>
                        </form>
                    </flux:menu>
                </flux:dropdown>
            </flux:sidebar>

            <!-- Mobile sidebar & top bar -->
            <div class="flex-1 flex flex-col">
                <flux:header class="lg:hidden bg-white/90 backdrop-blur border-b border-slate-200 px-4 py-3">
                    <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />
                    <a href="{{ route('dashboard') }}" class="ms-3 flex items-center gap-2" wire:navigate>
                        <x-app-logo-icon class="size-7 text-slate-900" />
                        <span class="font-semibold text-sm text-slate-800">
                            {{ config('app.name', 'Leads Dashboard') }}
                        </span>
                    </a>
                    <flux:spacer />
                    <flux:dropdown position="top" align="end">
                        <flux:profile
                            class="cursor-pointer"
                            :initials="auth()->user()->initials()"
                        />
                        <flux:menu class="w-56">
                            <flux:menu.item :href="route('settings.profile')" icon="cog" wire:navigate>
                                {{ __('Settings') }}
                            </flux:menu.item>
                            <flux:menu.separator />
                            <form method="POST" action="{{ route('logout') }}" class="w-full">
                                @csrf
                                <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                                    {{ __('Log Out') }}
                                </flux:menu.item>
                            </form>
                        </flux:menu>
                    </flux:dropdown>
                </flux:header>

                <!-- Mobile slide-out sidebar -->
                <flux:sidebar
                    stashable
                    sticky
                    class="lg:hidden w-64 border-none bg-gray-800 text-slate-100 py-6"
                >
                    <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-4 mb-6" wire:navigate>
                        <x-app-logo-icon class="size-8 text-white" />
                        <span class="font-semibold text-base text-white">
                            {{ config('app.name', 'Leads Dashboard') }}
                        </span>
                    </a>

                    <flux:navlist>
                        <flux:navlist.group :heading="__('Lead Generation')">
                            <flux:navlist.item
                                icon="layout-grid"
                                :href="route('dashboard')"
                                :current="request()->routeIs('dashboard') || request()->routeIs('leads.dashboard')"
                                wire:navigate
                                class="rounded-lg border-0 data-[current=true]:bg-orange-500 data-[current=true]:text-white hover:bg-orange-400/90 hover:text-white"
                            >
                                {{ __('Dashboard') }}
                            </flux:navlist.item>
                            <flux:navlist.item
                                icon="book-open-text"
                                :href="route('leads.all')"
                                :current="request()->routeIs('leads.all')"
                                wire:navigate
                                class="rounded-lg border-0 hover:bg-slate-800 hover:text-white data-[current=true]:bg-slate-800 data-[current=true]:text-white"
                            >
                                {{ __('All Leads') }}
                            </flux:navlist.item>
                            <flux:navlist.item
                                icon="plus"
                                :href="route('leads.create')"
                                :current="request()->routeIs('leads.create')"
                                wire:navigate
                                class="rounded-lg border-0 hover:bg-slate-800 hover:text-white data-[current=true]:bg-slate-800 data-[current=true]:text-white"
                            >
                                {{ __('Create Lead') }}
                            </flux:navlist.item>
                            <flux:navlist.item
                                icon="key"
                                :href="route('api-keys')"
                                :current="request()->routeIs('api-keys')"
                                wire:navigate
                                class="rounded-lg border-0 hover:bg-slate-800 hover:text-white data-[current=true]:bg-slate-800 data-[current=true]:text-white"
                            >
                                {{ __('API Keys') }}
                            </flux:navlist.item>
                        </flux:navlist.group>
                    </flux:navlist>
                </flux:sidebar>

                <!-- Main content area -->
                <div class="flex-1 flex flex-col ">
                    <header class="hidden lg:flex items-center justify-between px-10 pt-4 pb-4 bg-white shadow-sm border-b border-slate-200">
                        <div>
                            {{-- <p class="text-xs font-medium uppercase tracking-wider text-slate-400">
                                {{ __('Overview') }}
                            </p> --}}
                            <h1 class="text-2xl font-semibold text-slate-900">
                                {{ $title ?? __('Lead Intelligence Dashboard') }}
                            </h1>
                        </div>

                        <div class="flex items-center gap-4">
                            <div class="relative hidden md:block">
                                <input
                                    type="text"
                                    placeholder="{{ __('Search leads, companies...') }}"
                                    class="w-64 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm text-slate-700 shadow-sm focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-400"
                                >
                                <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-slate-400">
                                    <flux:icon name="magnifying-glass" class="size-4" />
                                </span>
                            </div>

                            <div class="flex items-center gap-2">
                                <span class="hidden text-xs font-medium text-slate-500 lg:inline">
                                    {{ __('Today') }}
                                </span>
                                <span class="rounded-full bg-orange-50 px-3 py-1 text-xs font-semibold text-orange-600">
                                    {{ now()->format('d M Y') }}
                                </span>
                            </div>
                        </div>
                    </header>

                    <main class="px-4 pb-8 pt-4 md:px-8 lg:px-10 lg:pt-2 space-y-6">
                        {{ $slot }}
                    </main>
                </div>
            </div>
        </div>

        @fluxScripts
    </body>
</html>
