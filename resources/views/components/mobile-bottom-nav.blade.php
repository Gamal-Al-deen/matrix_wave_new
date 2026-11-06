@php
    $bottomNav = [
        [
            'label' => 'الرئيسية',
            'route' => 'home',
            'icon' => 'home',
            'active' => request()->routeIs('home'),
        ],
        [
            'label' => 'المنتجات',
            'route' => 'products.index',
            'icon' => 'box',
            'active' => request()->routeIs('products.*'),
        ],
        [
            'label' => 'الطلبات',
            'route' => 'orders.index',
            'icon' => 'orders',
            'active' => request()->routeIs('orders.*'),
        ],
        [
            'label' => 'حسابي',
            'route' => 'account',
            'icon' => 'user',
            'active' => request()->routeIs('account*'),
        ],
    ];
@endphp

<nav
    class="fixed inset-x-0 bottom-0 z-40 border-t border-brand-ink/10 bg-white/95 pb-[env(safe-area-inset-bottom)] shadow-[0_-12px_35px_-22px_rgba(12,42,52,0.45)] backdrop-blur md:hidden"
    aria-label="التنقل السريع"
    data-mobile-bottom-nav
>
    <div class="mx-auto grid w-full max-w-md grid-cols-4 gap-1 px-2 pt-1.5">
        @foreach ($bottomNav as $item)
            @if (Route::has($item['route']))
                <a
                    href="{{ route($item['route']) }}"
                    @class([
                        'group flex min-h-14 flex-col items-center justify-center gap-1 rounded-xl px-1 py-1.5 text-[0.68rem] font-bold transition',
                        'text-brand-ink' => $item['active'],
                        'text-slate-500 hover:bg-surface-muted hover:text-brand-ink' => ! $item['active'],
                    ])
                    @if ($item['active']) aria-current="page" @endif
                >
                    <span @class([
                        'grid h-8 w-12 place-items-center rounded-full transition',
                        'bg-brand-teal/20 text-brand-ink' => $item['active'],
                        'bg-transparent text-slate-500 group-hover:bg-brand-teal/10 group-hover:text-brand-ink' => ! $item['active'],
                    ])>
                        <x-icon :name="$item['icon']" class="h-5 w-5" />
                    </span>
                    <span>{{ $item['label'] }}</span>
                </a>
            @endif
        @endforeach
    </div>
</nav>
