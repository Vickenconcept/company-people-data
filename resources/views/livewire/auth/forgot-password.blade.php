<div class="flex flex-col gap-6">
    <x-auth-header :title="__('Forgot password')" :description="__('Enter your email to receive a password reset link')" />

    <!-- Session Status -->
    <x-auth-session-status class="text-center" :status="session('status')" />

    <form wire:submit="sendPasswordResetLink" class="flex flex-col gap-6">
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

        <flux:button variant="primary" type="submit" class="w-full !rounded-2xl !border-none !bg-gradient-to-r !from-[#8f47f2] !to-[#5d30dc] !py-3 !text-white shadow-md shadow-violet-300/60 hover:opacity-95">
            {{ __('Email password reset link') }}
        </flux:button>
    </form>

    <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-slate-600">
        <span>{{ __('Or, return to') }}</span>
        <flux:link class="!text-violet-700 hover:!text-violet-800" :href="route('login')" wire:navigate>{{ __('log in') }}</flux:link>
    </div>
</div>
