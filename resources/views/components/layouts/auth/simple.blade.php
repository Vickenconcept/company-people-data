<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-[#f5f6fb] antialiased">
        <div class="flex min-h-screen items-center justify-center px-4 py-10">
            <div class=" w-full max-w-lg gap-8  items-center">
            {{-- <div class="grid w-full max-w-lg gap-8 md:grid-cols-[1.4fr,1fr] items-center"> --}}
            

                <!-- Right: Auth card -->
                <div class="flex w-full flex-col gap-6 justify-center items-center">
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
