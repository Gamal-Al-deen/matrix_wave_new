@extends('layouts.app')

@section('title', $product->name)
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags((string) $product->description), 155) ?: $product->name)

@section('content')
    @php
        $images = $product->images;
        $mainImage = $images->first();
        $available = $product->status->isOrderable();
    @endphp

    <div class="container-page py-7 sm:py-10">
        <x-breadcrumbs :items="[
            ['label' => 'الرئيسية', 'url' => route('home')],
            ['label' => 'المنتجات', 'url' => route('products.index')],
            ['label' => $product->name],
        ]" />

        <div class="grid items-start gap-6 md:grid-cols-[1.08fr_1fr] md:gap-6 lg:gap-10">
            <section aria-label="صور المنتج" class="card overflow-hidden p-3 sm:p-4">
                <div class="relative aspect-[4/3] overflow-hidden rounded-xl bg-surface-muted">
                    @if ($mainImage?->public_url)
                        <img
                            id="product-gallery-main"
                            src="{{ $mainImage->public_url }}"
                            alt="{{ $mainImage->alt_text ?: $product->name }}"
                            class="h-full w-full object-contain"
                            fetchpriority="high"
                            data-cart-product-image
                        >
                    @else
                        <span class="grid h-full place-items-center text-slate-400" aria-hidden="true">
                            <x-icon name="image" class="h-16 w-16" />
                        </span>
                    @endif

                    <span @class([
                        'badge absolute start-3 top-3 gap-1.5 border border-white/20 shadow-sm backdrop-blur',
                        'bg-success/95 text-white' => $available,
                        'bg-warning/95 text-white' => ! $available,
                    ])>
                        <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true"></span>
                        {{ $product->status->label() }}
                    </span>
                </div>

                @if ($images->count() > 1)
                    <div class="mt-3 grid grid-cols-5 gap-2" aria-label="صور المنتج المصغرة">
                        @foreach ($images as $image)
                            <button
                                type="button"
                                class="aspect-square overflow-hidden rounded-lg border border-brand-ink/10 bg-surface-muted transition hover:border-brand-teal focus-visible:outline-none aria-pressed:border-brand-teal aria-pressed:ring-2 aria-pressed:ring-brand-teal/40"
                                data-gallery-thumb
                                data-gallery-target="product-gallery-main"
                                data-gallery-src="{{ $image->public_url }}"
                                data-gallery-alt="{{ $image->alt_text ?: $product->name }}"
                                aria-label="عرض الصورة {{ $loop->iteration }} من {{ $images->count() }}"
                                aria-pressed="{{ $loop->first ? 'true' : 'false' }}"
                            >
                                <img
                                    src="{{ $image->public_url }}"
                                    alt=""
                                    class="h-full w-full object-cover"
                                    loading="lazy"
                                >
                            </button>
                        @endforeach
                    </div>
                @endif
            </section>

            <section class="space-y-5" aria-labelledby="product-title">
                @if ($product->category)
                    <a
                        href="{{ route('products.index', ['category' => $product->category->slug]) }}"
                        class="inline-flex min-h-11 items-center gap-1.5 rounded-full bg-brand-teal/15 px-3 py-1 text-xs font-bold text-brand-ink transition hover:bg-brand-teal/25"
                    >
                        <x-icon name="tags" class="h-4 w-4" />
                        {{ $product->category->name }}
                    </a>
                @endif

                <div>
                    <p class="text-xs font-bold text-slate-500">تفاصيل المنتج</p>
                    <h1 id="product-title" class="mt-1 text-2xl font-extrabold leading-tight text-brand-ink sm:text-3xl">
                        {{ $product->name }}
                    </h1>
                </div>

                <div class="flex flex-wrap items-end gap-x-4 gap-y-2 rounded-2xl border border-brand-ink/10 bg-white p-4">
                    <div>
                        <p class="text-xs font-bold text-slate-500">السعر</p>
                        <p class="mt-1 text-3xl font-extrabold text-brand-ink" dir="ltr">
                            {{ number_format((float) $product->price, 2) }} {{ $currency }}
                        </p>
                    </div>

                    <span class="badge {{ $product->status->badgeClasses() }}">
                        {{ $product->status->label() }}
                    </span>
                </div>

                @if ($product->description)
                    <div class="text-sm leading-8 text-slate-600">
                        {!! nl2br(e($product->description)) !!}
                    </div>
                @endif

                @if ($available)
                    <div class="space-y-3 rounded-2xl border border-brand-ink/10 bg-white p-4 sm:p-5">
                        <form method="POST" action="{{ route('cart.store', $product->slug) }}" data-submit-once data-add-to-cart>
                            @csrf
                            <input type="hidden" name="quantity" value="1">
                            <button type="submit" class="btn btn-primary w-full sm:w-auto" aria-label="أضف {{ $product->name }} إلى السلة">
                                <x-icon name="cart" class="h-5 w-5" />
                                أضف إلى السلة
                            </button>
                        </form>
                        <p class="flex items-start gap-2 text-xs leading-6 text-slate-500">
                            <x-icon name="info" class="mt-0.5 h-4 w-4 shrink-0 text-brand-teal" />
                            يمكنك تعديل الكمية أو حذف المنتج من صفحة السلة قبل إتمام الطلب.
                        </p>
                    </div>
                @else
                    <x-alert type="warning">
                        هذا المنتج غير متوفر حاليًا. يمكنك تصفح المنتجات المشابهة أو العودة لاحقًا.
                    </x-alert>

                    <span class="btn w-full cursor-not-allowed bg-slate-100 text-slate-600 sm:w-auto" aria-disabled="true">
                        <x-icon name="ban" class="h-5 w-5" />
                        غير متوفر
                    </span>
                @endif

                <div class="grid grid-cols-2 gap-2 text-xs">
                    <div class="flex items-center gap-2 rounded-xl bg-white p-3 text-slate-600">
                        <x-icon name="shield" class="h-5 w-5 shrink-0 text-brand-teal" />
                        <span>راجع طلبك قبل التأكيد</span>
                    </div>
                    <div class="flex items-center gap-2 rounded-xl bg-white p-3 text-slate-600">
                        <x-icon name="wallet" class="h-5 w-5 shrink-0 text-brand-teal" />
                        <span>اختيار المحفظة عند الطلب</span>
                    </div>
                </div>

                <p class="text-xs text-slate-500">
                    السعر معروض بالعملة الحالية للموقع: <span class="font-bold" dir="ltr">{{ $currency }}</span>
                </p>
            </section>
        </div>

        @if ($related->isNotEmpty())
            <section class="mt-12">
                <x-section-heading
                    title="منتجات مشابهة"
                    description="منتجات أخرى من التصنيف نفسه."
                    action-label="عرض الكل"
                    action-icon="arrow-left"
                    :action-url="route('products.index', ['category' => $product->category?->slug])"
                />

                <div class="grid grid-cols-1 gap-4 min-[420px]:grid-cols-2 sm:gap-5 md:grid-cols-3 xl:grid-cols-4">
                    @foreach ($related as $relatedProduct)
                        <x-product-card :product="$relatedProduct" :currency="$currency" />
                    @endforeach
                </div>
            </section>
        @endif
    </div>
@endsection
