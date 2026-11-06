@php
    $settings = \App\Models\SiteSetting::current();
    $appName = $settings?->displayName() ?? (string) config('app.name', 'Matrix Wave');
    $appDescription = trim((string) $settings?->description);
    $appDescription = $appDescription !== '' ? $appDescription : 'تصفح المنتجات والطلب بسهولة.';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0C2A34">
    <title>@yield('title', $appName) | {{ $appName }}</title>
    <meta name="description" content="{{ trim($__env->yieldContent('meta_description', $appDescription)) }}">

    @fonts

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="min-h-screen bg-surface-muted text-ink">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:start-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-white focus:px-4 focus:py-2 focus:text-sm focus:font-bold focus:text-brand-ink focus:shadow-lg">
        تخطي إلى المحتوى
    </a>

    <x-header :unread-count="$unreadCount ?? null" />

    <main id="main-content" class="min-h-[60vh]">
        @if (session('status'))
            <div class="container-page pt-4">
                <x-alert type="success" :message="session('status')" />
            </div>
        @endif

        @if (session('success'))
            <div class="container-page pt-4">
                <x-alert type="success" :message="session('success')" />
            </div>
        @endif

        @if (session('error'))
            <div class="container-page pt-4">
                <x-alert type="error" :message="session('error')" />
            </div>
        @endif

        @if ($errors->any())
            <div class="container-page pt-4">
                <x-alert type="error">
                    <ul class="list-inside list-disc space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </x-alert>
            </div>
        @endif

        @yield('content')
    </main>

    <x-footer />

    <x-mobile-bottom-nav />

    @auth
        <div
            data-notification-poller
            data-url="{{ route('notifications.poll') }}"
            data-enabled="{{ auth()->user()->browser_notifications_enabled ? '1' : '0' }}"
            hidden
        ></div>
    @endauth

    @stack('scripts')
</body>
</html>
