@extends('layouts.app')

@section('title', 'الرئيسية')

@php
    $description = $settings?->description
        ?: 'تصفح المنتجات، أضف ما يعجبك إلى السلة، وأكمل طلبك بخطوات بسيطة وآمنة.';
    $siteName = $settings?->displayName() ?? (string) config('app.name', 'Matrix Wave');
    $bannerCount = $banners->count();
@endphp

@section('content')
    @if ($banners->isNotEmpty())
        <section class="bg-brand-ink pb-5 pt-4 text-white sm:pb-7 sm:pt-6" aria-labelledby="home-banners-title" data-home-banners>
            <div class="container-page">
                <h1 id="home-banners-title" class="sr-only">عروض {{ $siteName }}</h1>

                <div
                    class="relative overflow-hidden rounded-3xl border border-white/10 bg-white/5 shadow-2xl shadow-black/20"
                    role="region"
                    aria-roledescription="carousel"
                    aria-label="بانرات المتجر"
                    tabindex="0"
                    data-carousel
                    data-interval="5000"
                >
                    <div class="relative h-[220px] w-full sm:h-[340px] lg:h-[420px]">
                        @foreach ($banners as $banner)
                            <article
                                @class([
                                    'carousel-slide absolute inset-0',
                                    'is-active' => $loop->first,
                                ])
                                data-carousel-slide
                                role="group"
                                aria-roledescription="slide"
                                aria-label="البانر {{ $loop->iteration }} من {{ $bannerCount }}"
                                aria-hidden="{{ $loop->first ? 'false' : 'true' }}"
                            >
                                @if ($banner->image_public_url)
                                    <img
                                        src="{{ $banner->image_public_url }}"
                                        alt="{{ $banner->alt_text }}"
                                        class="h-full w-full object-cover"
                                        loading="{{ $loop->first ? 'eager' : 'lazy' }}"
                                        fetchpriority="{{ $loop->first ? 'high' : 'auto' }}"
                                        decoding="async"
                                    >
                                @else
                                    <div class="grid h-full w-full place-items-center bg-brand-ink px-6 text-center text-sm text-white/70">
                                        {{ $banner->alt_text }}
                                    </div>
                                @endif

                                <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-brand-ink via-brand-ink/10 to-brand-ink/10" aria-hidden="true"></div>

                                <div class="absolute start-3 top-3 inline-flex items-center gap-1.5 rounded-full border border-white/15 bg-brand-ink/55 px-3 py-1 text-[0.68rem] font-bold text-white backdrop-blur sm:start-5 sm:top-5">
                                    <x-icon name="sparkles" class="h-3.5 w-3.5 text-brand-teal" />
                                    عرض مميز
                                </div>

                                @if (filled($banner->title))
                                    <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-brand-ink/95 via-brand-ink/45 to-transparent p-4 pb-24 sm:p-7 sm:pb-20">
                                        <h2 class="max-w-3xl text-lg font-extrabold leading-8 text-white sm:text-2xl lg:text-3xl" dir="auto">
                                            {{ $banner->title }}
                                        </h2>
                                    </div>
                                @endif
                            </article>
                        @endforeach
                    </div>

                    @if ($bannerCount > 1)
                        <div class="absolute inset-x-0 bottom-0 z-20 flex flex-wrap items-center justify-between gap-1.5 bg-gradient-to-t from-brand-ink/95 via-brand-ink/55 to-transparent px-3 pb-2.5 pt-8 sm:flex-nowrap sm:gap-2 sm:px-5 sm:pb-3">
                            <button
                                type="button"
                                class="order-2 grid h-11 w-11 shrink-0 place-items-center rounded-full border border-white/20 bg-brand-ink/55 text-white backdrop-blur transition hover:border-brand-teal/70 hover:bg-brand-teal hover:text-brand-ink focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-teal sm:order-1"
                                data-carousel-prev
                                aria-label="البانر السابق"
                            >
                                <x-icon name="chevron-right" class="h-5 w-5" />
                            </button>

                            <div class="order-first flex w-full min-w-0 items-center justify-center gap-1 overflow-x-auto px-1 pb-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden sm:order-2 sm:w-auto sm:flex-1 sm:justify-start sm:pb-0" role="group" aria-label="اختيار البانر">
                                @foreach ($banners as $banner)
                                    <button
                                        type="button"
                                        class="group relative grid h-8 w-7 shrink-0 place-items-center rounded-full focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-teal sm:h-11"
                                        data-carousel-dot
                                        aria-label="عرض البانر {{ $loop->iteration }} من {{ $bannerCount }}"
                                        aria-current="{{ $loop->first ? 'true' : 'false' }}"
                                    >
                                        <span
                                            @class([
                                                'block h-2.5 rounded-full transition-all duration-300',
                                                'w-6 bg-brand-teal' => $loop->first,
                                                'w-2.5 bg-white/50 group-hover:bg-white/80' => ! $loop->first,
                                            ])
                                            data-carousel-indicator
                                        ></span>
                                    </button>
                                @endforeach
                            </div>

                            <button
                                type="button"
                                class="order-3 grid h-11 w-11 shrink-0 place-items-center rounded-full border border-white/20 bg-brand-ink/55 text-white backdrop-blur transition hover:border-brand-teal/70 hover:bg-brand-teal hover:text-brand-ink focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-teal sm:order-3"
                                data-carousel-toggle
                                aria-label="إيقاف التبديل التلقائي"
                                aria-pressed="false"
                            >
                                <x-icon name="pause" class="h-4 w-4" data-carousel-pause-icon />
                                <x-icon name="play" class="hidden h-4 w-4" data-carousel-play-icon />
                            </button>

                            <button
                                type="button"
                                class="order-4 grid h-11 w-11 shrink-0 place-items-center rounded-full border border-white/20 bg-brand-ink/55 text-white backdrop-blur transition hover:border-brand-teal/70 hover:bg-brand-teal hover:text-brand-ink focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-teal"
                                data-carousel-next
                                aria-label="البانر التالي"
                            >
                                <x-icon name="chevron-left" class="h-5 w-5" />
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </section>

        <div class="container-page -mt-1 pb-2 sm:pb-4">
            <div class="grid grid-cols-2 gap-2.5 rounded-2xl border border-brand-ink/10 bg-white p-3 shadow-[0_14px_35px_-26px_rgba(12,42,52,0.4)] sm:grid-cols-4 sm:gap-3 sm:p-4">
                @foreach ([
                    ['icon' => 'box', 'title' => 'تصفح المنتجات', 'text' => 'اختر ما يناسبك'],
                    ['icon' => 'cart', 'title' => 'أضف إلى السلة', 'text' => 'راجع الكميات'],
                    ['icon' => 'wallet', 'title' => 'اختر المحفظة', 'text' => 'وسيلة الدفع المفضلة'],
                    ['icon' => 'orders', 'title' => 'تابع طلبك', 'text' => 'من حسابك بسهولة'],
                ] as $step)
                    <div class="flex items-center gap-2.5 rounded-xl bg-surface-muted/80 p-2.5 sm:p-3">
                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-brand-teal/15 text-brand-ink">
                            <x-icon :name="$step['icon']" class="h-5 w-5" />
                        </span>
                        <span class="min-w-0">
                            <span class="block text-[0.72rem] font-extrabold leading-4 text-brand-ink sm:text-xs">{{ $step['title'] }}</span>
                            <span class="mt-0.5 block text-[0.65rem] leading-4 text-slate-500 sm:text-[0.7rem]">{{ $step['text'] }}</span>
                        </span>
                    </div>
                @endforeach
            </div>
        </div>

        <section class="container-page py-7 sm:py-10">
            <div class="card flex flex-col gap-5 p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6">
                <div>
                    <p class="text-xs font-extrabold text-brand-teal">تجربة {{ $siteName }}</p>
                    <h2 class="mt-1 text-xl font-extrabold leading-8 text-brand-ink">منتجات مختارة، وتجربة طلب واضحة من البداية إلى النهاية.</h2>
                    <p class="mt-2 max-w-3xl text-sm leading-7 text-slate-600">{{ $description }}</p>
                </div>
                <div class="flex shrink-0 flex-wrap gap-3">
                    <a href="{{ route('products.index') }}" class="btn btn-primary">
                        <x-icon name="box" class="h-4 w-4" />
                        تصفح المنتجات
                    </a>
                    <a href="#categories" class="btn btn-outline">
                        <x-icon name="tags" class="h-4 w-4" />
                        التصنيفات
                    </a>
                </div>
            </div>
        </section>
    @else
        <section class="relative overflow-hidden bg-brand-ink text-white" data-home-hero>
            <div class="absolute inset-0 opacity-20" aria-hidden="true">
                <div class="absolute -start-24 top-10 h-64 w-64 rounded-full bg-brand-teal blur-3xl"></div>
                <div class="absolute -end-16 bottom-0 h-72 w-72 rounded-full bg-brand-orange blur-3xl"></div>
            </div>

            <div class="container-page relative grid items-center gap-8 py-14 sm:py-20 lg:grid-cols-[1.2fr_1fr]">
                <div class="space-y-5">
                    <p class="inline-flex items-center gap-2 rounded-full border border-brand-teal/30 bg-brand-teal/10 px-3 py-1 text-xs font-bold text-brand-teal">
                        <x-icon name="sparkles" class="h-3.5 w-3.5" />
                        متجر {{ $siteName }}
                    </p>
                    <h1 class="text-3xl font-extrabold leading-tight sm:text-4xl lg:text-5xl">
                        منتجات مختارة، وتجربة طلب واضحة من البداية إلى النهاية.
                    </h1>
                    <p class="max-w-2xl text-base leading-8 text-white/75">
                        {{ $description }}
                    </p>

                    <div class="flex flex-wrap gap-3">
                        <a href="{{ route('products.index') }}" class="btn btn-primary">
                            <x-icon name="box" class="h-4 w-4" />
                            تصفح المنتجات
                        </a>
                        <a href="#categories" class="btn btn-outline border-white/20 bg-white/5 text-white hover:bg-white/10">
                            <x-icon name="tags" class="h-4 w-4" />
                            التصنيفات
                        </a>
                    </div>
                </div>

                <div class="flex justify-center lg:justify-end">
                    <div class="w-full max-w-md rounded-3xl border border-white/10 bg-white/5 p-6 shadow-2xl backdrop-blur">
                        <x-logo class="mx-auto w-full max-w-sm" priority />
                        <p class="mt-4 text-center text-xs leading-6 text-white/60">
                            هوية {{ $siteName }} — الشعار الرسمي للموقع.
                        </p>
                    </div>
                </div>
            </div>
        </section>
    @endif

    <section id="categories" class="container-page py-7 sm:py-10">
        <x-section-heading
            title="التصنيفات"
            description="اختر التصنيف الذي يناسبك للوصول إلى المنتجات بسرعة."
            action-label="كل المنتجات"
            :action-url="route('products.index')"
        />

        @if ($categories->isEmpty())
            <x-empty-state
                title="لا توجد تصنيفات بعد"
                description="ستظهر التصنيفات هنا بعد إضافتها من لوحة الإدارة."
            />
        @else
            <ul class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                @foreach ($categories as $category)
                    <li>
                        <a
                            href="{{ route('products.index', ['category' => $category->slug]) }}"
                            class="card card-hover flex items-center justify-between gap-3 p-4"
                        >
                            <span class="flex min-w-0 items-center gap-2.5">
                                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-brand-teal/15 text-brand-ink">
                                    <x-icon name="tags" class="h-5 w-5" />
                                </span>
                                <span class="truncate text-sm font-extrabold text-brand-ink">{{ $category->name }}</span>
                            </span>
                            <span class="grid min-w-7 shrink-0 place-items-center rounded-full bg-surface-muted px-2 py-0.5 text-[0.7rem] font-extrabold text-slate-600">
                                {{ $category->products_count }}
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    <section class="container-page pb-10 sm:pb-14">
        <x-section-heading
            title="أحدث المنتجات"
            description="أحدث ما أضفناه إلى الكتالوج."
            action-label="عرض الكل"
            :action-url="route('products.index')"
        />

        @if ($products->isEmpty())
            <x-empty-state
                title="لا توجد منتجات معروضة حاليًا"
                description="عد لاحقًا لاكتشاف ما نضيفه من منتجات جديدة."
            />
        @else
            <div class="grid grid-cols-1 gap-4 min-[420px]:grid-cols-2 sm:gap-5 md:grid-cols-3 xl:grid-cols-4">
                @foreach ($products as $product)
                    <x-product-card :product="$product" :currency="$currency" />
                @endforeach
            </div>

            <div class="mt-8">{{ $products->links() }}</div>
        @endif
    </section>
@endsection
