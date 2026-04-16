<div class="flex flex-col gap-6">
    <x-auth-header :title="__('Reset password')" :description="__('Please enter your new password below')" />

    <!-- Session Status -->
    <x-auth-session-status class="text-center" :status="session('status')" />

    <form wire:submit="resetPassword" class="flex flex-col gap-6">
        <!-- Email Address -->
        <div class="space-y-1.5">
            <label class="text-xs font-semibold uppercase tracking-wide text-slate-600">{{ __('Email') }}</label>
            <input
                wire:model="email"
                type="email"
                required
                autocomplete="email"
                class="w-full rounded-2xl border border-violet-200 bg-violet-50/40 px-4 py-3 text-sm text-slate-900 placeholder:text-slate-400 shadow-[0_1px_2px_rgba(15,23,42,0.04)] transition-all focus:border-violet-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-violet-500/20"
            />
            @error('email') <p class="text-xs font-medium text-red-600">{{ $message }}</p> @enderror
        </div>

        <!-- Password -->
        <div class="space-y-1.5" x-data="{ showPassword: false }">
            <label class="text-xs font-semibold uppercase tracking-wide text-slate-600">{{ __('Password') }}</label>
            <div class="relative">
                <input
                    wire:model="password"
                    x-bind:type="showPassword ? 'text' : 'password'"
                    required
                    autocomplete="new-password"
                    placeholder="{{ __('Password') }}"
                    class="w-full rounded-2xl border border-violet-200 bg-violet-50/40 px-4 py-3 pr-12 text-sm text-slate-900 placeholder:text-slate-400 shadow-[0_1px_2px_rgba(15,23,42,0.04)] transition-all focus:border-violet-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-violet-500/20"
                />
                <button
                    type="button"
                    x-on:click="showPassword = !showPassword"
                    class="absolute inset-y-0 right-3 inline-flex items-center text-slate-400 hover:text-violet-700 transition-colors"
                    x-bind:aria-label="showPassword ? 'Hide password' : 'Show password'"
                >
                    <svg x-show="!showPassword" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    <svg x-show="showPassword" x-cloak class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a10.056 10.056 0 012.21-3.592M6.228 6.228A9.956 9.956 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.05 10.05 0 01-4.132 5.411M15 12a3 3 0 00-3-3m0 0a3 3 0 00-3 3m3-3v3m0 0H9m3 0h3M3 3l18 18" />
                    </svg>
                </button>
            </div>
            @error('password') <p class="text-xs font-medium text-red-600">{{ $message }}</p> @enderror
        </div>

        <!-- Confirm Password -->
        <div class="space-y-1.5" x-data="{ showConfirmPassword: false }">
            <label class="text-xs font-semibold uppercase tracking-wide text-slate-600">{{ __('Confirm password') }}</label>
            <div class="relative">
                <input
                    wire:model="password_confirmation"
                    x-bind:type="showConfirmPassword ? 'text' : 'password'"
                    required
                    autocomplete="new-password"
                    placeholder="{{ __('Confirm password') }}"
                    class="w-full rounded-2xl border border-violet-200 bg-violet-50/40 px-4 py-3 pr-12 text-sm text-slate-900 placeholder:text-slate-400 shadow-[0_1px_2px_rgba(15,23,42,0.04)] transition-all focus:border-violet-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-violet-500/20"
                />
                <button
                    type="button"
                    x-on:click="showConfirmPassword = !showConfirmPassword"
                    class="absolute inset-y-0 right-3 inline-flex items-center text-slate-400 hover:text-violet-700 transition-colors"
                    x-bind:aria-label="showConfirmPassword ? 'Hide password' : 'Show password'"
                >
                    <svg x-show="!showConfirmPassword" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    <svg x-show="showConfirmPassword" x-cloak class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a10.056 10.056 0 012.21-3.592M6.228 6.228A9.956 9.956 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.05 10.05 0 01-4.132 5.411M15 12a3 3 0 00-3-3m0 0a3 3 0 00-3 3m3-3v3m0 0H9m3 0h3M3 3l18 18" />
                    </svg>
                </button>
            </div>
        </div>

        <div class="flex items-center justify-end">
            <flux:button type="submit" variant="primary" class="w-full !rounded-2xl !border-none !bg-gradient-to-r !from-[#8f47f2] !to-[#5d30dc] !py-3 !text-white shadow-md shadow-violet-300/60 hover:opacity-95">
                {{ __('Reset password') }}
            </flux:button>
        </div>
    </form>
</div>
