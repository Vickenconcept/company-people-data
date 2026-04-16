<div class="flex flex-col gap-6">
    <x-auth-header :title="__('Create an account')" :description="__('Enter your details below to create your account')" />

    <!-- Session Status -->
    <x-auth-session-status class="text-center" :status="session('status')" />

    <form wire:submit="register" class="flex flex-col gap-6">
        <!-- Name -->
        <div class="space-y-1.5">
            <label class="text-xs font-semibold uppercase tracking-wide text-slate-600">{{ __('Name') }}</label>
            <input
                wire:model="name"
                type="text"
                required
                autofocus
                autocomplete="name"
                placeholder="{{ __('Full name') }}"
                class="w-full rounded-2xl border border-violet-200 bg-violet-50/40 px-4 py-3 text-sm text-slate-900 placeholder:text-slate-400 shadow-[0_1px_2px_rgba(15,23,42,0.04)] transition-all focus:border-violet-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-violet-500/20"
            />
            @error('name') <p class="text-xs font-medium text-red-600">{{ $message }}</p> @enderror
        </div>

        <!-- Email Address -->
        <div class="space-y-1.5">
            <label class="text-xs font-semibold uppercase tracking-wide text-slate-600">{{ __('Email address') }}</label>
            <input
                wire:model="email"
                type="email"
                required
                autocomplete="email"
                placeholder="email@example.com"
                class="w-full rounded-2xl border border-violet-200 bg-violet-50/40 px-4 py-3 text-sm text-slate-900 placeholder:text-slate-400 shadow-[0_1px_2px_rgba(15,23,42,0.04)] transition-all focus:border-violet-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-violet-500/20"
            />
            @error('email') <p class="text-xs font-medium text-red-600">{{ $message }}</p> @enderror
        </div>

        <!-- Password -->
        <div class="space-y-1.5">
            <label class="text-xs font-semibold uppercase tracking-wide text-slate-600">{{ __('Password') }}</label>
            <input
                wire:model="password"
                type="password"
                required
                autocomplete="new-password"
                placeholder="{{ __('Password') }}"
                class="w-full rounded-2xl border border-violet-200 bg-violet-50/40 px-4 py-3 text-sm text-slate-900 placeholder:text-slate-400 shadow-[0_1px_2px_rgba(15,23,42,0.04)] transition-all focus:border-violet-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-violet-500/20"
            />
            @error('password') <p class="text-xs font-medium text-red-600">{{ $message }}</p> @enderror
        </div>

        <!-- Confirm Password -->
        <div class="space-y-1.5">
            <label class="text-xs font-semibold uppercase tracking-wide text-slate-600">{{ __('Confirm password') }}</label>
            <input
                wire:model="password_confirmation"
                type="password"
                required
                autocomplete="new-password"
                placeholder="{{ __('Confirm password') }}"
                class="w-full rounded-2xl border border-violet-200 bg-violet-50/40 px-4 py-3 text-sm text-slate-900 placeholder:text-slate-400 shadow-[0_1px_2px_rgba(15,23,42,0.04)] transition-all focus:border-violet-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-violet-500/20"
            />
        </div>

        <div class="flex items-center justify-end">
            <flux:button type="submit" variant="primary" class="w-full !rounded-2xl !border-none !bg-gradient-to-r !from-[#8f47f2] !to-[#5d30dc] !py-3 !text-white shadow-md shadow-violet-300/60 hover:opacity-95">
                {{ __('Create account') }}
            </flux:button>
        </div>
    </form>

    <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-slate-600">
        <span>{{ __('Already have an account?') }}</span>
        <flux:link class="!text-violet-700 hover:!text-violet-800" :href="route('login')" wire:navigate>{{ __('Log in') }}</flux:link>
    </div>
</div>
