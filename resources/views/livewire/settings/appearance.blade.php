<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Appearance')" :subheading=" __('The app now uses a single light theme for maximum clarity.')">
        <p class="text-sm text-slate-500">
            {{ __('Theme switching has been disabled. All users see the same light interface.') }}
        </p>
    </x-settings.layout>
</section>
