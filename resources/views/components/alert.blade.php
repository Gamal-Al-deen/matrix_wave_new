@props([
    'type' => 'info',
    'message' => null,
])

@php
    $classes = [
        'success' => 'border-success/25 bg-success/10 text-success',
        'warning' => 'border-warning/25 bg-warning/10 text-warning',
        'error' => 'border-danger/25 bg-danger/10 text-danger',
        'info' => 'border-info/25 bg-info/10 text-info',
    ];
@endphp

<div
    role="alert"
    aria-live="polite"
    {{ $attributes->merge(['class' => 'rounded-xl border px-4 py-3 text-sm font-bold ' . ($classes[$type] ?? $classes['info'])]) }}
>
    {{ $message ?? $slot }}
</div>
