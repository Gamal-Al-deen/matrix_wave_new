@props([
    'status',
])

<span {{ $attributes->merge(['class' => 'inline-flex items-center whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-extrabold '.$status->badgeClasses()]) }}>
    {{ $status->label() }}
</span>
