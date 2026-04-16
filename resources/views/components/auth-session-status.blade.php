@props([
    'status',
])

@if ($status)
    <div {{ $attributes->merge(['class' => 'rounded-2xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-700']) }}>
        {{ $status }}
    </div>
@endif
