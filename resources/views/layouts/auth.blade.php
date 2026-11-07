@php
    $settings = \App\Models\SiteSetting::current();
    $appName = $settings?->displayName() ?? (string) config('app.name', 'Matrix Wave');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0C2A34">
    <title>@yield('title', 'تسجيل الدخول') | {{ $appName }}</title>

    @fonts

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="min-h-dvh bg-brand-ink text-ink">
    <div class="relative flex min-h-dvh items-center justify-center overflow-hidden px-4 py-8 sm:py-10">
        <div class="pointer-events-none absolute inset-0 opacity-25" aria-hidden="true">
            <div class="absolute -start-24 top-10 h-72 w-72 rounded-full bg-brand-teal blur-3xl"></div>
            <div class="absolute -end-20 bottom-0 h-80 w-80 rounded-full bg-brand-orange blur-3xl"></div>
        </div>

        <main class="relative grid w-full max-w-5xl items-stretch gap-0 overflow-hidden rounded-3xl border border-white/10 bg-white shadow-2xl lg:grid-cols-[0.9fr_1.1fr]">
            <section class="relative hidden overflow-hidden bg-brand-ink p-8 text-white lg:flex lg:flex-col lg:justify-between">
                <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-black/20 to-transparent" aria-hidden="true"></div>

                <div class="relative space-y-5">
                    <a href="{{ route('home') }}" class="inline-block rounded-xl" aria-label="{{ $appName }} — الرئيسية">
                        <x-logo class="w-[190px]" priority />
                    </a>

                    <div>
                        <p class="inline-flex items-center gap-2 rounded-full border border-brand-teal/30 bg-brand-teal/10 px-3 py-1 text-xs font-bold text-brand-teal">
                            <x-icon name="shield" class="h-3.5 w-3.5" />
                            دخول آمن
                        </p>
                        <h2 class="mt-4 text-2xl font-extrabold leading-9">أهلًا بك في {{ $appName }}</h2>
                        <p class="mt-2 text-sm leading-7 text-white/70">
                            أكمل طلباتك وتابعها بسهولة من حسابك، مع تجربة عربية واضحة على الهاتف والحاسوب.
                        </p>
                    </div>
                </div>

                <ul class="relative mt-8 space-y-3 text-sm text-white/75">
                    <li class="flex items-center gap-2.5">
                        <span class="grid h-8 w-8 place-items-center rounded-lg bg-white/10 text-brand-teal">
                            <x-icon name="orders" class="h-4 w-4" />
                        </span>
                        متابعة الطلبات وحالتها
                    </li>
                    <li class="flex items-center gap-2.5">
                        <span class="grid h-8 w-8 place-items-center rounded-lg bg-white/10 text-brand-teal">
                            <x-icon name="user" class="h-4 w-4" />
                        </span>
                        إدارة بيانات التواصل والعنوان
                    </li>
                    <li class="flex items-center gap-2.5">
                        <span class="grid h-8 w-8 place-items-center rounded-lg bg-white/10 text-brand-teal">
                            <x-icon name="cart" class="h-4 w-4" />
                        </span>
                        إتمام الطلب بخطوات واضحة
                    </li>
                </ul>
            </section>

            <section class="flex flex-col justify-center bg-white p-5 sm:p-8 lg:p-10">
                <div class="mb-6 flex flex-col items-center text-center lg:hidden">
                    <a href="{{ route('home') }}" class="rounded-xl" aria-label="{{ $appName }} — الرئيسية">
                        <x-logo class="w-[180px]" priority />
                    </a>
                </div>

                <div class="mb-6 text-center">
                    <h1 class="text-xl font-extrabold text-brand-ink sm:text-2xl">@yield('title', 'مرحبًا بعودتك')</h1>
                    @hasSection('subtitle')
                        <p class="mt-2 text-sm leading-7 text-slate-600">@yield('subtitle')</p>
                    @endif
                </div>

                @yield('content')

                <a href="{{ route('home') }}" class="tap-link mt-6 justify-center text-sm font-bold text-brand-ink/70 hover:text-brand-ink">
                    <x-icon name="arrow-right" class="h-4 w-4" />
                    العودة إلى الصفحة الرئيسية
                </a>
            </section>
        </main>
    </div>

    <x-toast-stack />

    @stack('scripts')
</body>
</html>
