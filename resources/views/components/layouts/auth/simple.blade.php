<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-[#f5f6fb] antialiased">
        <div class="flex min-h-screen items-center justify-center px-4 py-10">
            <div class="grid w-full max-w-5xl gap-8 md:grid-cols-[1.4fr,1fr] items-center">
                <!-- Left: Illustration / brand panel -->
                <div class="hidden md:flex h-full flex-col justify-between rounded-3xl bg-slate-900 px-8 py-8 text-white shadow-2xl">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-white/10">
                                <x-app-logo-icon class="size-7 text-white" />
                            </span>
                            <span class="text-sm font-semibold tracking-wide uppercase text-slate-100/80">
                                {{ config('app.name', 'Lead Studio') }}
                            </span>
                        </div>
                        <span class="rounded-full bg-white/10 px-3 py-1 text-xs font-medium text-slate-100/90">
                            {{ __('Lead Intelligence') }}
                        </span>
                    </div>

                    <div class="mt-10 space-y-4">
                        <h1 class="text-2xl font-semibold leading-tight">
                            {{ __('Turn raw websites into qualified pipeline, automatically.') }}
                        </h1>
                        <p class="text-sm text-slate-200/80">
                            {{ __('Scrape, enrich and email your ideal customers in one streamlined workspace, powered by AI and best‑in‑class data providers.') }}
                        </p>
                    </div>

                    <div class="mt-8 grid gap-3 text-xs text-slate-200/90">
                        <div class="flex items-center gap-2">
                            <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                            <span>{{ __('Smart ICP & lookalike company discovery') }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="h-2 w-2 rounded-full bg-orange-400"></span>
                            <span>{{ __('Verified contacts with outreach‑ready emails') }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="h-2 w-2 rounded-full bg-sky-400"></span>
                            <span>{{ __('Automated multi‑step campaigns you control') }}</span>
                        </div>
                    </div>
                </div>

                <!-- Right: Auth card -->
                <div class="flex w-full flex-col gap-6">
                    <a href="{{ route('home') }}" class="flex items-center gap-2 justify-center md:justify-start" wire:navigate>
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-white shadow-sm">
                            <x-app-logo-icon class="size-6 text-slate-900" />
                        </span>
                        <span class="sr-only">{{ config('app.name', 'Laravel') }}</span>
                    </a>

                    <div class="rounded-3xl border border-slate-100 bg-white px-8 py-8 shadow-lg shadow-slate-900/5">
                        {{ $slot }}
                    </div>
                </div>
            </div>
        </div>
        @fluxScripts
    </body>
</html>
