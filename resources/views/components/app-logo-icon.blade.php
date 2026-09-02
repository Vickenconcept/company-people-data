@props([])

<img
    src="{{ asset('favicon.svg') }}"
    alt="{{ config('app.name', 'Leads Dashboard') }}"
    {{ $attributes->class(['shrink-0 rounded-lg object-contain']) }}
/>
