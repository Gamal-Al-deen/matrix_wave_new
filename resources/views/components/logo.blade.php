@props([
    'class' => '',
    'alt' => null,
    'priority' => false,
])

@php
    $settings = \App\Models\SiteSetting::current();
    $siteName = $settings?->displayName() ?? (string) config('app.name', 'Matrix Wave');
    $alt = $alt ?? 'شعار '.$siteName;
    $logoSrc = $settings?->logoUrl() ?? asset('images/branding/matrix-wave-logo-2x.jpg');
    $usesFallback = ! ($settings?->hasCustomLogo() ?? false);
    $hasWidthClass = (bool) preg_match('/(?:^|\s)(?:[a-z0-9-]+:)?w-/', $class);
    $defaultWidth = $hasWidthClass ? '' : 'w-[160px] sm:w-[200px] ';
@endphp

<img
    src="{{ $logoSrc }}"
    @if ($usesFallback)
        srcset="{{ asset('images/branding/matrix-wave-logo-original.jpg') }} 1080w, {{ asset('images/branding/matrix-wave-logo-2x.jpg') }} 2160w, {{ asset('images/branding/matrix-wave-logo-3x.jpg') }} 3240w"
        sizes="(max-width: 640px) 180px, 220px"
        width="1080"
        height="354"
    @endif
    alt="{{ $alt }}"
    loading="{{ $priority ? 'eager' : 'lazy' }}"
    fetchpriority="{{ $priority ? 'high' : 'auto' }}"
    decoding="async"
    {{ $attributes->merge(['class' => 'block h-auto max-w-full ' . $defaultWidth . $class]) }}
>
