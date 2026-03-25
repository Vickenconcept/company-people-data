<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-[#f5f6fb] text-slate-900 antialiased">
        <div class="min-h-screen flex">
            <!-- Left sidebar -->
            <flux:sidebar
                sticky
                stashable
                class="hidden lg:flex w-72 flex-col items-start gap-4 border-r border-slate-200 bg-white text-slate-800 py-6 shadow-sm"
            >
                <div class="w-full px-4">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3 w-full rounded-2xl border border-slate-200 bg-slate-50 px-3 py-3 no-underline" wire:navigate>
                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-orange-500 ring-1 ring-orange-300/50">
                            <x-app-logo-icon class="size-5 text-white" />
                        </span>
                        <div class="min-w-0">
                            <div class="truncate text-sm font-semibold text-slate-900">
                                {{ config('app.name', 'Leads Dashboard') }}
                            </div>
                            <div class="text-xs text-slate-500">
                                Outreach Workspace
                            </div>
                        </div>
                    </a>
                </div>

                <div class="w-full px-4 pt-2">
                    <p class="mb-2 px-2 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Navigation</p>
                </div>
                @php
                    $navDashboard = request()->routeIs('dashboard') || request()->routeIs('leads.dashboard');
                    $navLeads = request()->routeIs('leads.all') || request()->routeIs('leads.details');
                    $navCreate = request()->routeIs('leads.create') || request()->routeIs('leads.import');
                    // $navKeys = request()->routeIs('api-keys');
                    $navEmailTemplates = request()->routeIs('leads.email-templates');
                @endphp
                <nav class="flex w-full flex-col gap-1.5 px-4" aria-label="{{ __('Main navigation') }}">
                    <a
                        href="{{ route('dashboard') }}"
                        wire:navigate
                        @if($navDashboard) aria-current="page" @endif
                        @class([
                            'flex w-full items-center gap-3 rounded-xl border border-transparent px-3 py-2.5 text-sm font-semibold no-underline transition-colors',
                            '!bg-orange-500 !text-white shadow-sm ring-1 !ring-orange-300/60' => $navDashboard,
                            '!text-slate-700 !bg-transparent hover:!bg-slate-100 hover:!text-slate-900' => ! $navDashboard,
                        ])
                    >
                        <flux:icon name="layout-grid" @class(['size-5 shrink-0', 'text-white' => $navDashboard, 'text-slate-500' => ! $navDashboard]) />
                        {{ __('Dashboard') }}
                    </a>
                    <a
                        href="{{ route('leads.all') }}"
                        wire:navigate
                        @if($navLeads) aria-current="page" @endif
                        @class([
                            'flex w-full items-center gap-3 rounded-xl border border-transparent px-3 py-2.5 text-sm font-semibold no-underline transition-colors',
                            '!bg-orange-500 !text-white shadow-sm ring-1 !ring-orange-300/60' => $navLeads,
                            '!text-slate-700 !bg-transparent hover:!bg-slate-100 hover:!text-slate-900' => ! $navLeads,
                        ])
                    >
                        <flux:icon name="book-open-text" @class(['size-5 shrink-0', 'text-white' => $navLeads, 'text-slate-500' => ! $navLeads]) />
                        {{ __('All Leads') }}
                    </a>
                    <a
                        href="{{ route('leads.create') }}"
                        wire:navigate
                        @if($navCreate) aria-current="page" @endif
                        @class([
                            'flex w-full items-center gap-3 rounded-xl border border-transparent px-3 py-2.5 text-sm font-semibold no-underline transition-colors',
                            '!bg-orange-500 !text-white shadow-sm ring-1 !ring-orange-300/60' => $navCreate,
                            '!text-slate-700 !bg-transparent hover:!bg-slate-100 hover:!text-slate-900' => ! $navCreate,
                        ])
                    >
                        <flux:icon name="plus" @class(['size-5 shrink-0', 'text-white' => $navCreate, 'text-slate-500' => ! $navCreate]) />
                        {{ __('Create Lead') }}
                    </a>
                    {{-- <a
                        href="{{ route('api-keys') }}"
                        wire:navigate
                        @if($navKeys) aria-current="page" @endif
                        @class([
                            'flex w-full items-center gap-3 rounded-xl border border-transparent px-3 py-2.5 text-sm font-semibold no-underline transition-colors',
                            '!bg-orange-500 !text-white shadow-sm ring-1 !ring-orange-300/60' => $navKeys,
                            '!text-slate-700 !bg-transparent hover:!bg-slate-100 hover:!text-slate-900' => ! $navKeys,
                        ])
                    >
                        <flux:icon name="key" @class(['size-5 shrink-0', 'text-white' => $navKeys, 'text-slate-500' => ! $navKeys]) />
                        {{ __('API Keys') }}
                    </a> --}}
                    <a
                        href="{{ route('leads.email-templates') }}"
                        wire:navigate
                        @if($navEmailTemplates) aria-current="page" @endif
                        @class([
                            'flex w-full items-center gap-3 rounded-xl border border-transparent px-3 py-2.5 text-sm font-semibold no-underline transition-colors',
                            '!bg-orange-500 !text-white shadow-sm ring-1 !ring-orange-300/60' => $navEmailTemplates,
                            '!text-slate-700 !bg-transparent hover:!bg-slate-100 hover:!text-slate-900' => ! $navEmailTemplates,
                        ])
                    >
                        <flux:icon name="key" @class(['size-5 shrink-0', 'text-white' => $navEmailTemplates, 'text-slate-500' => ! $navEmailTemplates]) />
                        {{ __('Email Templates') }}
                    </a>
                </nav>

                <flux:spacer />

                <flux:dropdown position="top" align="start">
                    <button class="mx-4 flex w-[calc(100%-2rem)] items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-left text-slate-800 hover:bg-slate-100 transition-colors">
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-orange-100 text-orange-700 font-semibold text-xs">
                            {{ auth()->user()->initials() }}
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-semibold text-slate-900">{{ auth()->user()->name }}</span>
                            <span class="block truncate text-xs text-slate-500">{{ auth()->user()->email }}</span>
                        </span>
                    </button>

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
                    class="lg:hidden w-72 border-r border-slate-200 bg-white text-slate-800 py-6"
                >
                    <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />
                    <div class="px-4 mb-6">
                        <a href="{{ route('dashboard') }}" class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-3 py-3 no-underline" wire:navigate>
                            <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-orange-500 ring-1 ring-orange-300/50">
                                <x-app-logo-icon class="size-5 text-white" />
                            </span>
                            <div class="min-w-0">
                                <div class="truncate text-base font-semibold text-slate-900">
                                    {{ config('app.name', 'Leads Dashboard') }}
                                </div>
                                <div class="text-xs text-slate-500">
                                    Outreach Workspace
                                </div>
                            </div>
                        </a>
                    </div>

                    @php
                        $mNavDashboard = request()->routeIs('dashboard') || request()->routeIs('leads.dashboard');
                        $mNavLeads = request()->routeIs('leads.all') || request()->routeIs('leads.details') || request()->routeIs('leads.email-templates');
                        $mNavCreate = request()->routeIs('leads.create') || request()->routeIs('leads.import');
                        $mNavKeys = request()->routeIs('api-keys');
                    @endphp
                    <div class="px-4">
                        <p class="mb-2 px-2 text-[11px] font-semibold uppercase tracking-wider text-slate-500">{{ __('Lead Generation') }}</p>
                        <nav class="flex flex-col gap-1" aria-label="{{ __('Lead generation navigation') }}">
                            <a
                                href="{{ route('dashboard') }}"
                                wire:navigate
                                @if($mNavDashboard) aria-current="page" @endif
                                @class([
                                    'flex w-full items-center gap-3 rounded-lg border border-transparent px-3 py-2.5 text-sm font-semibold no-underline transition-colors',
                                    '!bg-orange-500 !text-white shadow-sm ring-1 !ring-orange-300/60' => $mNavDashboard,
                                    '!text-slate-700 !bg-transparent hover:!bg-slate-100 hover:!text-slate-900' => ! $mNavDashboard,
                                ])
                            >
                                <flux:icon name="layout-grid" @class(['size-5 shrink-0', 'text-white' => $mNavDashboard, 'text-slate-500' => ! $mNavDashboard]) />
                                {{ __('Dashboard') }}
                            </a>
                            <a
                                href="{{ route('leads.all') }}"
                                wire:navigate
                                @if($mNavLeads) aria-current="page" @endif
                                @class([
                                    'flex w-full items-center gap-3 rounded-lg border border-transparent px-3 py-2.5 text-sm font-semibold no-underline transition-colors',
                                    '!bg-orange-500 !text-white shadow-sm ring-1 !ring-orange-300/60' => $mNavLeads,
                                    '!text-slate-700 !bg-transparent hover:!bg-slate-100 hover:!text-slate-900' => ! $mNavLeads,
                                ])
                            >
                                <flux:icon name="book-open-text" @class(['size-5 shrink-0', 'text-white' => $mNavLeads, 'text-slate-500' => ! $mNavLeads]) />
                                {{ __('All Leads') }}
                            </a>
                            <a
                                href="{{ route('leads.create') }}"
                                wire:navigate
                                @if($mNavCreate) aria-current="page" @endif
                                @class([
                                    'flex w-full items-center gap-3 rounded-lg border border-transparent px-3 py-2.5 text-sm font-semibold no-underline transition-colors',
                                    '!bg-orange-500 !text-white shadow-sm ring-1 !ring-orange-300/60' => $mNavCreate,
                                    '!text-slate-700 !bg-transparent hover:!bg-slate-100 hover:!text-slate-900' => ! $mNavCreate,
                                ])
                            >
                                <flux:icon name="plus" @class(['size-5 shrink-0', 'text-white' => $mNavCreate, 'text-slate-500' => ! $mNavCreate]) />
                                {{ __('Create Lead') }}
                            </a>
                            <a
                                href="{{ route('api-keys') }}"
                                wire:navigate
                                @if($mNavKeys) aria-current="page" @endif
                                @class([
                                    'flex w-full items-center gap-3 rounded-lg border border-transparent px-3 py-2.5 text-sm font-semibold no-underline transition-colors',
                                    '!bg-orange-500 !text-white shadow-sm ring-1 !ring-orange-300/60' => $mNavKeys,
                                    '!text-slate-700 !bg-transparent hover:!bg-slate-100 hover:!text-slate-900' => ! $mNavKeys,
                                ])
                            >
                                <flux:icon name="key" @class(['size-5 shrink-0', 'text-white' => $mNavKeys, 'text-slate-500' => ! $mNavKeys]) />
                                {{ __('API Keys') }}
                            </a>
                        </nav>
                    </div>
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
                            <livewire:layout.header-search />

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
