@props([
    'title',
    'description',
])

<div class="flex w-full flex-col text-center">
    <flux:heading size="xl" class="!text-slate-900">{{ $title }}</flux:heading>
    <flux:subheading class="!mt-1 !text-slate-500">{{ $description }}</flux:subheading>
</div>
