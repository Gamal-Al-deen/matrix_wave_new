@php
    $siteName = $siteName ?? config('app.name', 'Matrix Wave');
    $logoUrl = $logoUrl ?? asset('images/branding/matrix-wave-logo-2x.jpg');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'فاتورة') | {{ $siteName }}</title>

    @fonts

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="bg-white text-ink">
    <main class="invoice-page mx-auto w-full max-w-4xl bg-white p-6 sm:p-10">
        <header class="invoice-header mb-8 flex flex-col gap-4 rounded-2xl bg-brand-ink p-5 text-white sm:flex-row sm:items-center sm:justify-between">
            <img
                src="{{ $logoUrl }}"
                width="1080"
                height="354"
                alt="شعار {{ $siteName }}"
                class="block h-auto w-[180px] sm:w-[220px]"
            >
            <div class="text-start sm:text-end">
                <p class="invoice-header-site-name text-sm font-bold text-brand-teal">{{ $siteName }}</p>
                <p class="invoice-header-subtitle text-xs text-white/60">فاتورة طلب</p>
            </div>
        </header>

        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>
