@extends('layouts.app')

@section('title', 'سلة المشتريات')
@section('meta_description', 'راجع منتجات سلة المشتريات وعدّل الكميات قبل إتمام الطلب.')

@section('content')
    @php
        $money = fn (int $cents): string => number_format($cents / 100, 2);
    @endphp

    <div class="container-page py-8 sm:py-12">
        <x-breadcrumbs :items="[
            ['label' => 'الرئيسية', 'url' => route('home')],
            ['label' => 'المنتجات', 'url' => route('products.index')],
            ['label' => 'سلة المشتريات'],
        ]" />

        <x-section-heading
            title="سلة المشتريات"
            description="راجع المنتجات والكميات والأسعار الحالية قبل متابعة الطلب."
        />

        @if (empty($summary['lines']))
            <x-empty-state
                title="سلتك فارغة"
                description="لم تُضف أي منتج إلى السلة بعد. تصفّح المنتجات واختر ما يناسبك."
                action-label="تصفّح المنتجات"
                :action-url="route('products.index')"
            />
        @else
            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_340px]">
                <section class="space-y-4" aria-label="عناصر السلة">
                    @foreach ($summary['lines'] as $line)
                        @php
                            $product = $line['product'];
                            $image = $product?->images->first();
                        @endphp

                        <article @class([
                            'card p-4 sm:p-5',
                            'border-warning/50' => ! $line['available'],
                        ])>
                            <div class="flex gap-4">
                                <div class="h-24 w-24 shrink-0 overflow-hidden rounded-xl bg-surface-muted">
                                    @if ($image?->public_url)
                                        <img
                                            src="{{ $image->public_url }}"
                                            alt="{{ $image->alt_text ?: $product->name }}"
                                            class="h-full w-full object-cover"
                                            loading="lazy"
                                        >
                                    @else
                                        <span class="grid h-full place-items-center text-slate-400" aria-hidden="true">
                                            <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                                <rect x="3" y="4" width="18" height="16" rx="2"></rect>
                                                <circle cx="9" cy="10" r="1.5"></circle>
                                                <path d="m21 15-5-5L5 20"></path>
                                            </svg>
                                        </span>
                                    @endif
                                </div>

                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-start justify-between gap-2">
                                        <div class="min-w-0">
                                            @if ($product)
                                                <h2 class="line-clamp-2 text-base font-extrabold leading-7 text-brand-ink">
                                                    <a href="{{ route('products.show', $product->slug) }}" class="hover:text-brand-ink/80">
                                                        {{ $product->name }}
                                                    </a>
                                                </h2>
                                                <p class="mt-1 text-xs font-bold text-slate-500">{{ $product->category?->name }}</p>
                                            @else
                                                <h2 class="text-base font-extrabold text-slate-500">منتج غير متوفر</h2>
                                            @endif
                                        </div>

                                        @if ($line['available'])
                                            <p class="text-base font-extrabold text-brand-ink" dir="ltr">
                                                {{ $money($line['line_total_cents']) }} {{ $currency }}
                                            </p>
                                        @endif
                                    </div>

                                    @if ($line['unit_price_cents'] !== null)
                                        <p class="mt-2 text-xs text-slate-500">
                                            سعر الوحدة:
                                            <span class="font-bold text-slate-700" dir="ltr">{{ $money($line['unit_price_cents']) }} {{ $currency }}</span>
                                        </p>
                                    @endif

                                    @unless ($line['available'])
                                        <div class="mt-3">
                                            <x-alert type="warning" :message="$line['issue']" />
                                        </div>
                                    @endunless

                                    <div class="mt-3 flex flex-wrap items-end justify-between gap-3">
                                        @if ($line['available'])
                                            <form
                                                method="POST"
                                                action="{{ route('cart.update', $line['product_id']) }}"
                                                class="flex flex-wrap items-end gap-2"
                                            >
                                                @csrf
                                                @method('PATCH')

                                                <div>
                                                    <label for="quantity-{{ $line['product_id'] }}" class="mb-1 block text-xs font-bold text-slate-600">
                                                        الكمية
                                                    </label>
                                                    <input
                                                        id="quantity-{{ $line['product_id'] }}"
                                                        name="quantity"
                                                        type="number"
                                                        min="1"
                                                        max="{{ (int) config('cart.max_quantity', 1000000) }}"
                                                        step="1"
                                                        value="{{ $line['quantity'] }}"
                                                        class="input w-24 px-3 py-2 text-center"
                                                        dir="ltr"
                                                        inputmode="numeric"
                                                    >
                                                </div>

                                                <button type="submit" class="btn btn-outline px-3 py-2 text-xs">
                                                    تحديث الكمية
                                                </button>
                                            </form>
                                        @endif

                                        <x-confirm-form
                                            :action="route('cart.destroy', $line['product_id'])"
                                            method="DELETE"
                                            title="حذف المنتج من السلة"
                                            message="هل تريد حذف هذا المنتج من السلة؟ يمكنك إضافته مرة أخرى لاحقًا."
                                            label="حذف من السلة"
                                        />
                                    </div>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </section>

                <aside aria-label="ملخص السلة">
                    <div class="card space-y-4 p-5 lg:sticky lg:top-28">
                        <h2 class="text-lg font-extrabold text-brand-ink">ملخص السلة</h2>

                        <dl class="space-y-2 text-sm">
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-slate-600">عدد العناصر</dt>
                                <dd class="font-bold text-brand-ink">{{ $summary['quantity'] }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-3 border-t border-brand-ink/10 pt-3">
                                <dt class="font-bold text-brand-ink">المجموع الفرعي</dt>
                                <dd class="text-lg font-extrabold text-brand-ink" dir="ltr">
                                    {{ $money($summary['subtotal_cents']) }} {{ $currency }}
                                </dd>
                            </div>
                        </dl>

                        <p class="text-xs leading-6 text-slate-500">
                            الأسعار المعروضة حالية وتُحسب من بيانات المنتجات عند العرض. قد تتغير قبل تأكيد الطلب.
                        </p>

                        @if ($summary['has_issues'])
                            <x-alert type="warning">
                                بعض المنتجات في سلتك غير متوفرة حاليًا، ولن تُحتسب في المجموع الفرعي. احذفها أو استبدلها قبل إتمام الطلب.
                            </x-alert>
                        @endif

                        @if ($summary['has_issues'])
                            <button
                                type="button"
                                class="btn btn-primary w-full cursor-not-allowed opacity-60"
                                aria-disabled="true"
                                title="احذف المنتجات غير المتوفرة أو استبدلها أولًا"
                            >
                                إتمام الطلب
                            </button>
                        @else
                            <a href="{{ route('checkout.index') }}" class="btn btn-primary w-full">
                                إتمام الطلب
                            </a>
                        @endif

                        @guest
                            <p class="text-center text-xs leading-6 text-slate-500">
                                سيُطلب منك تسجيل الدخول للمتابعة إلى الدفع.
                            </p>
                        @endguest

                        <a href="{{ route('products.index') }}" class="btn btn-outline w-full">متابعة التسوق</a>
                    </div>
                </aside>
            </div>
        @endif
    </div>
@endsection
