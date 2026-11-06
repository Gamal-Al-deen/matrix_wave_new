@props([
    'unreadCount' => null,
])

@php
    $settings = \App\Models\SiteSetting::current();
    $siteName = $settings?->displayName() ?? (string) config('app.name', 'Matrix Wave');
    $cartCount = app(\App\Services\Cart\CartService::class)->count();
    $unreadNotifications = $unreadCount ?? (auth()->check() && Route::has('notifications.index')
        ? auth()->user()->unreadNotifications()->count()
        : 0);
    $navItems = [
        ['label' => 'الرئيسية', 'route' => 'home', 'icon' => 'home', 'active' => request()->routeIs('home')],
        ['label' => 'المنتجات', 'route' => 'products.index', 'icon' => 'box', 'active' => request()->routeIs('products.*')],
        ['label' => 'من نحن', 'route' => 'about', 'icon' => 'info', 'active' => request()->routeIs('about')],
    ];
@endphp

<header class="sticky top-0 z-40 border-b border-white/10 bg-brand-ink/95 text-white shadow-lg backdrop-blur">
    <div class="container-page flex items-center gap-1.5 py-2.5 sm:gap-4 sm:py-3">
        <a href="{{ route('home') }}" class="shrink-0 rounded-lg focus-visible:outline-brand-teal" aria-label="{{ $siteName }} — الرئيسية">
            <x-logo priority class="w-[104px] min-[380px]:w-[128px] sm:w-[170px] md:w-[160px] lg:w-[190px]" />
        </a>

        <div role="search" class="hidden min-w-0 flex-1 md:block">
            <form method="GET" action="{{ route('products.index') }}">
                <label for="site-search" class="sr-only">ابحث عن منتج</label>
                <div class="relative">
                    <input
                        id="site-search"
                        name="q"
                        type="search"
                        value="{{ request()->routeIs('products.index') ? request('q') : '' }}"
                        placeholder="ابحث باسم المنتج..."
                        maxlength="100"
                        class="min-h-11 w-full rounded-xl border border-white/15 bg-white/10 py-2.5 pe-11 ps-4 text-sm text-white placeholder:text-white/60 focus:border-brand-teal focus:outline-none focus:ring-2 focus:ring-brand-teal/30"
                    >
                    <button type="submit" class="absolute inset-y-0 end-0 grid w-11 place-items-center rounded-e-xl text-white/70 hover:text-white" aria-label="بحث">
                        <x-icon name="search" class="h-5 w-5" />
                    </button>
                </div>
            </form>
        </div>

        <nav class="ms-auto flex items-center gap-0.5 sm:gap-1.5" aria-label="حساب المستخدم والسلة">
            @auth
                @if (Route::has('account'))
                    <a href="{{ route('account') }}" class="hidden min-h-11 items-center gap-2 rounded-lg px-2.5 py-2 text-sm font-bold text-white/90 hover:bg-white/10 hover:text-white sm:flex">
                        <x-icon name="user" class="h-5 w-5" />
                        <span>حسابي</span>
                    </a>
                @else
                    <span class="hidden items-center gap-2 rounded-lg px-2.5 py-2 text-sm font-bold text-white/45 sm:flex" aria-disabled="true" title="قريبًا">
                        <x-icon name="user" class="h-5 w-5" />
                        <span>حسابي</span>
                        <span class="rounded-full bg-white/10 px-1.5 py-0.5 text-[0.6rem] font-bold text-white/60">قريبًا</span>
                    </span>
                @endif

                @if (Route::has('orders.index'))
                    <a
                        href="{{ route('orders.index') }}"
                        @class([
                            'hidden min-h-11 items-center gap-2 rounded-lg px-2.5 py-2 text-sm font-bold sm:flex',
                            'text-brand-teal' => request()->routeIs('orders.*'),
                            'text-white/90 hover:bg-white/10 hover:text-white' => ! request()->routeIs('orders.*'),
                        ])
                        @if (request()->routeIs('orders.*')) aria-current="page" @endif
                    >
                        <x-icon name="orders" class="h-5 w-5" />
                        <span>طلباتي</span>
                    </a>
                @endif

                @if (Route::has('notifications.index'))
                    <a
                        href="{{ route('notifications.index') }}"
                        class="relative inline-flex min-h-11 min-w-11 items-center justify-center rounded-lg px-2.5 py-2 text-sm font-bold text-white/90 hover:bg-white/10 hover:text-white"
                        aria-label="الإشعارات{{ $unreadNotifications > 0 ? '، '.$unreadNotifications.' غير مقروءة' : '' }}"
                        @if (request()->routeIs('notifications.*')) aria-current="page" @endif
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
                @endif

                @if (auth()->user()?->isAdmin())
                    <a href="{{ route('admin.dashboard') }}" class="hidden min-h-11 items-center gap-1.5 rounded-lg px-2.5 py-2 text-sm font-bold text-brand-teal hover:bg-white/10 lg:inline-flex">
                        <x-icon name="dashboard" class="h-5 w-5" />
                        الإدارة
                    </a>
                @endif

                @if (Route::has('logout'))
                    <form action="{{ route('logout') }}" method="POST" class="hidden lg:block">
                        @csrf
                        <button type="submit" class="inline-flex min-h-11 items-center gap-1.5 rounded-lg px-2.5 py-2 text-sm font-bold text-white/90 hover:bg-white/10 hover:text-white">
                            <x-icon name="logout" class="h-5 w-5" />
                            خروج
                        </button>
                    </form>
                @endif
            @else
                @if (Route::has('login'))
                    <a href="{{ route('login') }}" class="hidden min-h-11 items-center gap-1.5 rounded-lg px-2.5 py-2 text-sm font-bold text-white/90 hover:bg-white/10 hover:text-white sm:inline-flex">
                        <x-icon name="user" class="h-5 w-5" />
                        دخول
                    </a>
                @else
                    <span class="hidden items-center gap-1.5 rounded-lg px-2.5 py-2 text-sm font-bold text-white/45 sm:inline-flex" aria-disabled="true" title="قريبًا">
                        دخول
                        <span class="rounded-full bg-white/10 px-1.5 py-0.5 text-[0.6rem] font-bold text-white/60">قريبًا</span>
                    </span>
                @endif

                @if (Route::has('register'))
                    <a href="{{ route('register') }}" class="hidden min-h-11 items-center rounded-lg bg-white/10 px-3 py-2 text-sm font-bold text-white hover:bg-white/15 sm:inline-flex">تسجيل</a>
                @else
                    <span class="hidden items-center gap-1.5 rounded-lg bg-white/5 px-3 py-2 text-sm font-bold text-white/45 sm:inline-flex" aria-disabled="true" title="قريبًا">
                        تسجيل
                        <span class="rounded-full bg-white/10 px-1.5 py-0.5 text-[0.6rem] font-bold text-white/60">قريبًا</span>
                    </span>
                @endif
            @endauth

            @if (Route::has('cart.index'))
                <a href="{{ route('cart.index') }}" class="relative inline-flex min-h-11 min-w-11 items-center justify-center gap-2 rounded-lg px-2.5 py-2 text-sm font-bold text-white/90 hover:bg-white/10 hover:text-white" aria-label="سلة المشتريات — {{ $cartCount }} {{ $cartCount === 1 ? 'منتج' : 'منتجات' }}">
                    <x-icon name="cart" class="h-5 w-5" />
                    <span class="hidden lg:inline">السلة</span>
                    <span class="absolute end-0.5 top-0.5 grid h-5 min-w-5 place-items-center rounded-full bg-brand-orange px-1 text-[0.7rem] font-extrabold text-brand-ink lg:static lg:end-auto lg:top-auto" aria-hidden="true">{{ $cartCount }}</span>
                </a>
            @else
                <span class="relative inline-flex min-h-11 cursor-not-allowed items-center gap-2 rounded-lg px-2.5 py-2 text-sm font-bold text-white/45" aria-disabled="true" aria-label="سلة المشتريات" title="السلة قريبًا">
                    <x-icon name="cart" class="h-5 w-5" />
                    <span class="hidden lg:inline">السلة</span>
                    <span class="rounded-full bg-white/10 px-1.5 py-0.5 text-[0.6rem] font-bold text-white/60">قريبًا</span>
                </span>
            @endif

            <button
                type="button"
                class="inline-flex h-11 w-11 items-center justify-center rounded-lg text-white/90 hover:bg-white/10 hover:text-white md:hidden"
                data-mobile-menu-toggle
                aria-expanded="false"
                aria-controls="mobile-menu"
                aria-label="فتح القائمة"
            >
                <x-icon name="menu" class="h-6 w-6" />
            </button>
        </nav>
    </div>

    <div class="hidden border-t border-white/10 md:block">
        <nav class="container-page flex items-center gap-1 py-2" aria-label="التنقل الرئيسي">
            @foreach ($navItems as $item)
                @if (Route::has($item['route']))
                    <a
                        href="{{ route($item['route']) }}"
                        @class([
                            'inline-flex min-h-11 items-center gap-2 rounded-lg px-3 py-1 text-sm font-bold transition',
                            'bg-white/10 text-brand-teal' => $item['active'],
                            'text-white/80 hover:bg-white/5 hover:text-white' => ! $item['active'],
                        ])
                        @if ($item['active']) aria-current="page" @endif
                    >
                        <x-icon :name="$item['icon']" class="h-4 w-4" />
                        {{ $item['label'] }}
                    </a>
                @else
                    <span class="inline-flex min-h-11 cursor-not-allowed items-center gap-1.5 rounded-md px-2 py-1 text-sm font-bold text-white/45" aria-disabled="true" title="قريبًا">
                        {{ $item['label'] }}
                        <span class="rounded-full bg-white/10 px-1.5 py-0.5 text-[0.6rem] font-bold text-white/60">قريبًا</span>
                    </span>
                @endif
            @endforeach
        </nav>
    </div>

    <div id="mobile-menu" class="hidden max-h-[calc(100dvh-4rem)] overflow-y-auto overscroll-contain border-t border-white/10 md:hidden">
        <div class="container-page space-y-3 py-4">
            <form method="GET" action="{{ route('products.index') }}" role="search" class="flex items-center gap-2">
                <label for="mobile-search" class="sr-only">ابحث عن منتج</label>
                <input
                    id="mobile-search"
                    name="q"
                    type="search"
                    value="{{ request()->routeIs('products.index') ? request('q') : '' }}"
                    placeholder="ابحث باسم المنتج..."
                    maxlength="100"
                    class="min-h-11 w-full rounded-xl border border-white/15 bg-white/10 px-4 py-2.5 text-sm text-white placeholder:text-white/60 focus:border-brand-teal focus:outline-none focus:ring-2 focus:ring-brand-teal/30"
                >
                <button type="submit" class="btn btn-primary shrink-0 gap-1.5 px-4 py-2.5 text-xs">
                    <x-icon name="search" class="h-4 w-4" />
                    بحث
                </button>
            </form>

            <nav class="grid gap-1" aria-label="التنقل على الهاتف">
                @foreach ($navItems as $item)
                    @if (Route::has($item['route']))
                        <a
                            href="{{ route($item['route']) }}"
                            @class([
                                'flex min-h-11 items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-bold',
                                'bg-white/10 text-brand-teal' => $item['active'],
                                'text-white/85 hover:bg-white/10 hover:text-white' => ! $item['active'],
                            ])
                            @if ($item['active']) aria-current="page" @endif
                        >
                            <x-icon :name="$item['icon']" class="h-5 w-5" />
                            {{ $item['label'] }}
                        </a>
                    @else
                        <span class="flex min-h-11 items-center justify-between gap-2 rounded-lg px-3 py-2 text-sm font-bold text-white/45" aria-disabled="true" title="قريبًا">
                            {{ $item['label'] }}
                            <span class="rounded-full bg-white/10 px-1.5 py-0.5 text-[0.6rem] font-bold text-white/60">قريبًا</span>
                        </span>
                    @endif
                @endforeach

                <div class="my-1 h-px bg-white/10" aria-hidden="true"></div>

                @auth
                    @if (Route::has('account'))
                        <a href="{{ route('account') }}" class="flex min-h-11 items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-bold text-white/85 hover:bg-white/10 hover:text-white">
                            <x-icon name="user" class="h-5 w-5" />
                            حسابي
                        </a>
                    @endif

                    @if (Route::has('orders.index'))
                        <a href="{{ route('orders.index') }}" class="flex min-h-11 items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-bold text-white/85 hover:bg-white/10 hover:text-white">
                            <x-icon name="orders" class="h-5 w-5" />
                            طلباتي
                        </a>
                    @endif

                    @if (Route::has('notifications.index'))
                        <a href="{{ route('notifications.index') }}" class="flex min-h-11 items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-bold text-white/85 hover:bg-white/10 hover:text-white">
                            <x-icon name="bell" class="h-5 w-5" />
                            الإشعارات
                            <span
                                data-notification-badge
                                @class([
                                    'ms-auto rounded-full bg-brand-orange px-2 py-0.5 text-[0.7rem] font-extrabold text-brand-ink',
                                    'hidden' => $unreadNotifications === 0,
                                ])
                                aria-hidden="true"
                            >{{ $unreadNotifications > 99 ? '99+' : $unreadNotifications }}</span>
                        </a>
                    @endif

                    @if (auth()->user()?->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="flex min-h-11 items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-bold text-brand-teal hover:bg-white/10">
                            <x-icon name="dashboard" class="h-5 w-5" />
                            لوحة الإدارة
                            <span
                                data-notification-badge
                                @class([
                                    'ms-auto rounded-full bg-brand-orange px-2 py-0.5 text-[0.7rem] font-extrabold text-brand-ink',
                                    'hidden' => $unreadNotifications === 0,
                                ])
                                aria-hidden="true"
                            >{{ $unreadNotifications > 99 ? '99+' : $unreadNotifications }}</span>
                        </a>
                    @endif

                    @if (Route::has('logout'))
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="flex min-h-11 w-full items-center gap-2.5 rounded-lg px-3 py-2 text-start text-sm font-bold text-white/85 hover:bg-white/10 hover:text-white">
                                <x-icon name="logout" class="h-5 w-5" />
                                تسجيل الخروج
                            </button>
                        </form>
                    @endif
                @else
                    @if (Route::has('login'))
                        <a href="{{ route('login') }}" class="flex min-h-11 items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-bold text-white/85 hover:bg-white/10 hover:text-white">
                            <x-icon name="user" class="h-5 w-5" />
                            تسجيل الدخول
                        </a>
                    @endif

                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="flex min-h-11 items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-bold text-white/85 hover:bg-white/10 hover:text-white">
                            <x-icon name="plus" class="h-5 w-5" />
                            إنشاء حساب
                        </a>
                    @endif
                @endauth
            </nav>
        </div>
    </div>
</header>
