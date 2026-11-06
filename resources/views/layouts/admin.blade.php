@php
    $settings = \App\Models\SiteSetting::current();
    $appName = $settings?->displayName() ?? (string) config('app.name', 'Matrix Wave');
    $adminUser = auth()->user();
    $unreadNotifications = $adminUser?->unreadNotifications()->count() ?? 0;
    $pendingOrders = \App\Models\Order::query()
        ->where('status', \App\Enums\OrderStatus::Pending->value)
        ->count();
    $adminNav = [
        ['label' => 'لوحة التحكم', 'route' => 'admin.dashboard'],
        ['label' => 'المنتجات', 'route' => 'admin.products.index'],
        ['label' => 'التصنيفات', 'route' => 'admin.categories.index'],
        ['label' => 'الطلبات', 'route' => 'admin.orders.index'],
        ['label' => 'العملاء', 'route' => 'admin.customers.index'],
        ['label' => 'المحافظ', 'route' => 'admin.wallets.index'],
        ['label' => 'البانرات', 'route' => 'admin.banners.index'],
        ['label' => 'الروابط الاجتماعية', 'route' => 'admin.social-links.index'],
        ['label' => 'الإعدادات', 'route' => 'admin.settings.edit'],
        ['label' => 'من نحن', 'route' => 'admin.about.edit'],
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0C2A34">
    <title>@yield('title', 'لوحة الإدارة') | {{ $appName }}</title>

    @fonts

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="min-h-screen bg-surface-muted text-ink">
    <div id="admin-sidebar-overlay" class="fixed inset-0 z-40 hidden bg-brand-ink/60 lg:hidden" data-admin-sidebar-overlay></div>

    <aside id="admin-sidebar" class="fixed inset-y-0 right-0 z-50 flex w-72 max-w-[85vw] translate-x-full flex-col bg-brand-ink text-white transition-transform duration-200 lg:translate-x-0" aria-label="قائمة الإدارة">
        <div class="flex items-center justify-between gap-3 border-b border-white/10 px-5 py-4">
            @if (Route::has('admin.dashboard'))
                <a href="{{ route('admin.dashboard') }}" class="rounded-lg" aria-label="لوحة إدارة {{ $appName }}">
                    <x-logo class="w-[160px]" />
                </a>
            @else
                <span class="rounded-lg" aria-label="لوحة إدارة {{ $appName }}">
                    <x-logo class="w-[160px]" />
                </span>
            @endif

            <button type="button" class="inline-grid h-11 w-11 place-items-center rounded-lg text-white/80 hover:bg-white/10 hover:text-white lg:hidden" data-admin-sidebar-close aria-label="إغلاق القائمة">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M6 6l12 12M18 6 6 18"></path>
                </svg>
            </button>
        </div>

        <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4" aria-label="أقسام الإدارة">
            @foreach ($adminNav as $item)
                @php
                    $available = Route::has($item['route']);
                    $active = $available && request()->routeIs($item['route']);
                @endphp

                @if ($available)
                    <a
                        href="{{ route($item['route']) }}"
                        @class([
                            'flex items-center justify-between gap-2 rounded-xl px-3 py-2.5 text-sm font-bold transition',
                            'bg-white/10 text-brand-teal' => $active,
                            'text-white/75 hover:bg-white/10 hover:text-white' => ! $active,
                        ])
                        @if ($active) aria-current="page" @endif
                    >
                        <span>{{ $item['label'] }}</span>

                        @if ($item['route'] === 'admin.orders.index')
                            <span
                                data-admin-orders-badge
                                @class([
                                    'grid h-5 min-w-5 place-items-center rounded-full bg-brand-orange px-1.5 text-[0.65rem] font-extrabold text-brand-ink',
                                    'hidden' => $pendingOrders === 0,
                                ])
                                aria-hidden="true"
                            >{{ $pendingOrders > 99 ? '99+' : $pendingOrders }}</span>
                        @endif
                    </a>
                @else
                    <span class="flex cursor-not-allowed items-center justify-between gap-2 rounded-xl px-3 py-2.5 text-sm font-bold text-white/45" aria-disabled="true" title="قريبًا">
                        {{ $item['label'] }}
                        <span class="rounded-full bg-white/10 px-1.5 py-0.5 text-[0.6rem] font-bold text-white/60">قريبًا</span>
                    </span>
                @endif
            @endforeach
        </nav>

        <div class="border-t border-white/10 p-4">
            <p class="mb-3 text-xs text-white/60">
                مسجل الدخول:
                <span class="font-bold text-white">{{ auth()->user()?->name ?? 'مدير' }}</span>
            </p>

            @if (Route::has('logout'))
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-outline w-full border-white/20 bg-white/5 text-white hover:bg-white/10">تسجيل الخروج</button>
                </form>
            @else
                <button type="button" class="btn btn-outline w-full cursor-not-allowed border-white/20 bg-white/5 text-white/45" aria-disabled="true" title="قريبًا" disabled>
                    تسجيل الخروج
                    <span class="rounded-full bg-white/10 px-1.5 py-0.5 text-[0.6rem] font-bold text-white/60">قريبًا</span>
                </button>
            @endif
        </div>
    </aside>

    <div class="lg:ms-72" data-admin-main>
        <header class="sticky top-0 z-30 border-b border-brand-ink/10 bg-white/95 backdrop-blur">
            <div class="flex items-center gap-1.5 px-4 py-3 sm:gap-3 sm:px-6 lg:px-8">
                <button type="button" class="inline-grid h-11 w-11 shrink-0 place-items-center rounded-lg text-brand-ink hover:bg-brand-ink/5 lg:hidden" data-admin-sidebar-toggle aria-controls="admin-sidebar" aria-expanded="false" aria-label="فتح قائمة الإدارة">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>

                <div class="min-w-0 flex-1">
                    <h1 class="truncate text-base font-extrabold text-brand-ink sm:text-lg">@yield('title', 'لوحة التحكم')</h1>
                </div>

                <a
                    href="{{ route('admin.orders.index', ['status' => 'pending']) }}"
                    class="relative inline-flex min-h-11 min-w-11 shrink-0 items-center justify-center gap-1.5 rounded-lg px-2 text-brand-ink hover:bg-brand-ink/5"
                    aria-label="الطلبات المعلقة{{ $pendingOrders > 0 ? '، '.$pendingOrders : '' }}"
                >
                    <x-icon name="orders" class="h-5 w-5" />
                    <span class="hidden text-xs font-bold sm:inline">معلقة</span>
                    <span
                        data-admin-orders-badge
                        @class([
                            'absolute end-1 top-1 grid h-5 min-w-5 place-items-center rounded-full bg-brand-orange px-1 text-[0.65rem] font-extrabold text-brand-ink sm:static sm:end-auto sm:top-auto',
                            'hidden' => $pendingOrders === 0,
                        ])
                        aria-hidden="true"
                    >{{ $pendingOrders > 99 ? '99+' : $pendingOrders }}</span>
                </a>

                <a
                    href="{{ route('notifications.index') }}"
                    class="relative inline-flex min-h-11 min-w-11 shrink-0 items-center justify-center rounded-lg px-2 text-brand-ink hover:bg-brand-ink/5"
                    aria-label="الإشعارات{{ $unreadNotifications > 0 ? '، '.$unreadNotifications.' غير مقروءة' : '' }}"
                >
                    <x-icon name="bell" class="h-5 w-5" />
                    <span
                        data-notification-badge
                        @class([
                            'absolute end-1 top-1 grid h-5 min-w-5 place-items-center rounded-full bg-brand-orange px-1 text-[0.7rem] font-extrabold text-brand-ink',
                            'hidden' => $unreadNotifications === 0,
                        ])
                        aria-hidden="true"
                    >{{ $unreadNotifications > 99 ? '99+' : $unreadNotifications }}</span>
                </a>

                <a href="{{ route('home') }}" class="inline-flex min-h-11 shrink-0 items-center rounded-lg px-2 text-xs font-bold text-slate-500 hover:bg-brand-ink/5 hover:text-brand-ink" aria-label="عرض الموقع">
                    <span class="hidden sm:inline">عرض الموقع</span>
                    <x-icon name="external" class="h-4 w-4 sm:hidden" />
                </a>
            </div>
        </header>

        <main class="p-4 sm:p-6 lg:p-8">
            @if (session('status'))
                <div class="mb-4"><x-alert type="success" :message="session('status')" /></div>
            @endif

            @if (session('error'))
                <div class="mb-4"><x-alert type="error" :message="session('error')" /></div>
            @endif

            @if ($errors->any())
                <div class="mb-4">
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
    </div>

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
