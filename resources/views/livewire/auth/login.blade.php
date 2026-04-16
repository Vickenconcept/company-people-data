<div class="flex flex-col gap-6 w-full max-w-xl">
    <x-auth-header :title="__('Log in to your account')" :description="__('Enter your email and password below to log in')" />

    <!-- Session Status -->
    <x-auth-session-status class="text-center" :status="session('status')" />

    <form wire:submit="login" class="flex flex-col gap-6">
        <!-- Email Address -->
        <div class="space-y-1.5">
            <label class="text-xs font-semibold uppercase tracking-wide text-slate-600">{{ __('Email address') }}</label>
            <input
                wire:model="email"
                type="email"
                required
                autofocus
                autocomplete="email"
                placeholder="email@example.com"
                class="w-full rounded-2xl border border-violet-200 bg-violet-50/40 px-4 py-3 text-sm text-slate-900 placeholder:text-slate-400 shadow-[0_1px_2px_rgba(15,23,42,0.04)] transition-all focus:border-violet-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-violet-500/20"
            />
            @error('email') <p class="text-xs font-medium text-red-600">{{ $message }}</p> @enderror
        </div>

        <!-- Password -->
        <div class="space-y-1.5">
            <div class="flex items-center justify-between">
                <label class="text-xs font-semibold uppercase tracking-wide text-slate-600">{{ __('Password') }}</label>
                @if (Route::has('password.request'))
                    <a class="text-sm font-semibold text-violet-700 hover:text-violet-800" href="{{ route('password.request') }}" wire:navigate>
                        {{ __('Forgot your password?') }}
                    </a>
                @endif
            </div>
            <input
                wire:model="password"
                type="password"
                required
                autocomplete="current-password"
                placeholder="{{ __('Password') }}"
                class="w-full rounded-2xl border border-violet-200 bg-violet-50/40 px-4 py-3 text-sm text-slate-900 placeholder:text-slate-400 shadow-[0_1px_2px_rgba(15,23,42,0.04)] transition-all focus:border-violet-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-violet-500/20"
            />
            @error('password') <p class="text-xs font-medium text-red-600">{{ $message }}</p> @enderror

        </div>

        <!-- Remember Me -->
        {{-- <flux:checkbox wire:model="remember" class="text-zinc-600" :label="__('Remember me')" /> --}}

        <div class="flex items-center justify-end">
            <flux:button variant="primary" type="submit" class="w-full !rounded-2xl !border-none !bg-gradient-to-r !from-[#8f47f2] !to-[#5d30dc] !py-3 !text-white shadow-md shadow-violet-300/60 hover:opacity-95">{{ __('Log in') }}</flux:button>
        </div>
    </form>

    @if (Route::has('register'))
        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-slate-600">
            <span>{{ __('Don\'t have an account?') }}</span>
            <flux:link class="!text-violet-700 hover:!text-violet-800" :href="route('register')" wire:navigate>{{ __('Sign up') }}</flux:link>
        </div>
    @endif
</div>
