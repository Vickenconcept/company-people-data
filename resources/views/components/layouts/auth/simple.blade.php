<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-[#efedf7] antialiased">
        <div class="relative flex min-h-screen items-center justify-center px-4 py-10">
            <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_20%_20%,rgba(143,71,242,0.18),transparent_40%),radial-gradient(circle_at_80%_15%,rgba(124,58,237,0.14),transparent_35%),radial-gradient(circle_at_50%_100%,rgba(99,102,241,0.12),transparent_40%)]"></div>
            <div class="relative w-full max-w-lg gap-8 items-center">
            {{-- <div class="grid w-full max-w-lg gap-8 md:grid-cols-[1.4fr,1fr] items-center"> --}}
            

                <!-- Right: Auth card -->
                <div class="flex w-full flex-col gap-6 justify-center items-center">
                    <a href="{{ route('home') }}" class="flex items-center gap-2 justify-center md:justify-start" wire:navigate>
                        <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-white/85 ring-1 ring-violet-200 shadow-sm backdrop-blur">
                            <x-app-logo-icon class="size-6 text-violet-700" />
                        </span>
                        <span class="sr-only">{{ config('app.name', 'Laravel') }}</span>
                    </a>

                    <div class="w-full rounded-3xl border border-violet-100/80 bg-white/95 px-8 py-8 shadow-xl shadow-violet-900/5 backdrop-blur">
                        {{ $slot }}
                    </div>
                </div>
            </div>
        </div>
        @fluxScripts
    </body>
</html>
