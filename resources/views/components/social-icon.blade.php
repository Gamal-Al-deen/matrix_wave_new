@props([
    'platform',
])

@php
    $value = $platform instanceof \App\Enums\SocialPlatform ? $platform->value : (string) $platform;
@endphp

<svg
    {{ $attributes->merge([
        'class' => 'h-4 w-4',
        'viewBox' => '0 0 24 24',
        'fill' => 'none',
        'stroke' => 'currentColor',
        'stroke-width' => '1.8',
        'stroke-linecap' => 'round',
        'stroke-linejoin' => 'round',
        'aria-hidden' => 'true',
        'focusable' => 'false',
    ]) }}
>
    @switch ($value)
        @case ('facebook')
            <path d="M14 8h2V4h-2a5 5 0 0 0-5 5v2H6v4h3v5h4v-5h3l1-4h-4V9a1 1 0 0 1 1-1Z" fill="currentColor" stroke="none"></path>
            @break

        @case ('instagram')
            <rect x="3" y="3" width="18" height="18" rx="5"></rect>
            <circle cx="12" cy="12" r="4"></circle>
            <circle cx="17.2" cy="6.8" r="0.8" fill="currentColor" stroke="none"></circle>
            @break

        @case ('x')
            <path d="M4 4l16 16M20 4 4 20" stroke-width="2.2"></path>
            @break

        @case ('youtube')
            <rect x="3" y="6" width="18" height="12" rx="3"></rect>
            <path d="m10 9 5 3-5 3V9Z" fill="currentColor" stroke="none"></path>
            @break

        @case ('tiktok')
            <path d="M14 4v9a4 4 0 1 1-4-4"></path>
            <path d="M14 4c1 2 2.5 3 5 3"></path>
            @break

        @case ('linkedin')
            <rect x="3" y="3" width="18" height="18" rx="2"></rect>
            <path d="M8 10v7M8 7v.01M12 17v-4a2.5 2.5 0 0 1 5 0v4M12 10v7"></path>
            @break

        @case ('telegram')
            <path d="M21 4 3 11l6 2 2 6 3-4 5 3 2-14Z"></path>
            @break

        @case ('snapchat')
            <path d="M12 3c3 0 5 2 5 5v3c0 .7.3 1.3.9 1.8l1.1.9-2.7 1.2c.3 1.1 1.2 2 2.7 2.8-1.5 1-3.5 1.3-5 1.8-1 1-2 1.5-3 1.5s-2-.5-3-1.5c-1.5-.5-3.5-.8-5-1.8 1.5-.8 2.4-1.7 2.7-2.8L3 13.7l1.1-.9c.6-.5.9-1.1.9-1.8V8c0-3 2-5 5-5Z"></path>
            @break

        @case ('whatsapp')
            <path d="M20 12a8 8 0 0 1-11.8 7L4 20l1-4.1A8 8 0 1 1 20 12Z"></path>
            <path d="M9 9c.5 2 2 3.5 4 4l1.5-1.5 2 1"></path>
            @break

        @default
            <circle cx="12" cy="12" r="8"></circle>
            <path d="M12 8v4l2.5 2"></path>
    @endswitch
</svg>
