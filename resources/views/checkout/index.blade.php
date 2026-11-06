@extends('layouts.app')

@section('title', 'إتمام الطلب')
@section('meta_description', 'راجع بيانات التواصل وعنوان التوصيل والمحفظة الاختيارية قبل تأكيد الطلب.')

@section('content')
    @php
        $money = fn (int $cents): string => number_format($cents / 100, 2);
        $user = auth()->user();
        $selectedWalletId = (string) old('wallet_id', $draft['wallet_id'] ?? '');
        $hasSelectedWallet = $wallets->contains(fn ($wallet): bool => (string) $wallet->id === $selectedWalletId);
    @endphp

    <div class="container-page py-8 sm:py-12">
        <x-breadcrumbs :items="[
            ['label' => 'الرئيسية', 'url' => route('home')],
            ['label' => 'المنتجات', 'url' => route('products.index')],
            ['label' => 'السلة', 'url' => route('cart.index')],
            ['label' => 'إتمام الطلب'],
        ]" />

        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <x-section-heading
                title="إتمام الطلب"
                description="أكمل بيانات التواصل والتوصيل، ثم راجع المنتجات قبل التأكيد."
            />

            <a href="{{ route('cart.index') }}" class="text-sm font-bold text-brand-ink underline decoration-brand-teal decoration-2 underline-offset-4 hover:text-brand-ink/80">
                العودة إلى السلة
            </a>
        </div>

        <form method="POST" action="{{ route('checkout.store') }}" class="mt-6" data-submit-once>
            @csrf
            <input type="hidden" name="checkout_token" value="{{ $checkoutToken }}">

            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_360px]">
                <div class="space-y-5">
                    <section class="card" aria-labelledby="contact-heading">
                        <div class="flex items-start gap-3 border-b border-brand-ink/10 p-5">
                            <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-brand-teal/20 text-sm font-extrabold text-brand-ink" aria-hidden="true">1</span>
                            <div>
                                <h2 id="contact-heading" class="text-lg font-extrabold text-brand-ink">بيانات التواصل</h2>
                                <p class="mt-1 text-sm leading-7 text-slate-600">
                                    راجع الاسم والهاتف والبريد الإلكتروني. ستُحفظ هذه البيانات مع الطلب.
                                </p>
                            </div>
                        </div>

                        <div class="grid gap-4 p-5 sm:grid-cols-2">
                            <x-form-field label="الاسم الكامل" name="name" :required="true">
                                <input
                                    id="name"
                                    name="name"
                                    type="text"
                                    value="{{ old('name', $draft['name'] ?? $user->name) }}"
                                    required
                                    maxlength="150"
                                    autocomplete="name"
                                    class="input @error('name') border-danger @enderror"
                                >
                            </x-form-field>

                            <x-form-field label="رقم الهاتف" name="phone" :required="true" hint="يمكنك استخدام الأرقام العربية أو الإنجليزية والرمز +.">
                                <input
                                    id="phone"
                                    name="phone"
                                    type="tel"
                                    value="{{ old('phone', $draft['phone'] ?? $user->phone) }}"
                                    required
                                    maxlength="40"
                                    inputmode="tel"
                                    autocomplete="tel"
                                    dir="ltr"
                                    aria-describedby="phone-hint"
                                    class="input text-start @error('phone') border-danger @enderror"
                                >
                            </x-form-field>

                            <div class="sm:col-span-2">
                                <x-form-field label="البريد الإلكتروني (اختياري)" name="email" hint="اتركه فارغًا إذا لم ترغب في إضافته إلى الطلب.">
                                    <input
                                        id="email"
                                        name="email"
                                        type="email"
                                        value="{{ old('email', array_key_exists('email', $draft) ? $draft['email'] : $user->email) }}"
                                        maxlength="255"
                                        inputmode="email"
                                        autocomplete="email"
                                        dir="ltr"
                                        aria-describedby="email-hint"
                                        class="input text-start @error('email') border-danger @enderror"
                                    >
                                </x-form-field>
                            </div>
                        </div>
                    </section>

                    <section class="card" aria-labelledby="address-heading">
                        <div class="flex items-start gap-3 border-b border-brand-ink/10 p-5">
                            <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-brand-teal/20 text-sm font-extrabold text-brand-ink" aria-hidden="true">2</span>
                            <div>
                                <h2 id="address-heading" class="text-lg font-extrabold text-brand-ink">عنوان التوصيل</h2>
                                <p class="mt-1 text-sm leading-7 text-slate-600">
                                    اكتب عنوانًا واضحًا يساعد على الوصول إليك.
                                </p>
                            </div>
                        </div>

                        <div class="p-5">
                            <x-form-field label="العنوان الكامل" name="delivery_address" :required="true" hint="المدينة، الحي، الشارع، وأي تفاصيل إضافية.">
                                <textarea
                                    id="delivery_address"
                                    name="delivery_address"
                                    rows="4"
                                    required
                                    maxlength="{{ (int) config('checkout.address_max', 1000) }}"
                                    autocomplete="street-address"
                                    aria-describedby="delivery_address-hint"
                                    class="input @error('delivery_address') border-danger @enderror"
                                >{{ old('delivery_address', $draft['delivery_address'] ?? $user->default_address) }}</textarea>
                            </x-form-field>
                        </div>
                    </section>

                    <section class="card" aria-labelledby="wallet-heading">
                        <div class="flex items-start gap-3 border-b border-brand-ink/10 p-5">
                            <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-brand-teal/20 text-sm font-extrabold text-brand-ink" aria-hidden="true">3</span>
                            <div>
                                <h2 id="wallet-heading" class="text-lg font-extrabold text-brand-ink">المحفظة الإلكترونية (اختياري)</h2>
                                <p class="mt-1 text-sm leading-7 text-slate-600">
                                    اختر محفظة إن رغبت، أو تابع دون تحديد محفظة.
                                </p>
                            </div>
                        </div>

                        <div class="space-y-3 p-5">
                            @if ($wallets->isEmpty())
                                <x-alert type="info">
                                    لا توجد محافظ مفعّلة حاليًا. يمكنك المتابعة دون اختيار محفظة.
                                </x-alert>
                            @endif

                            <fieldset class="space-y-2">
                                <legend class="sr-only">اختيار المحفظة الإلكترونية</legend>

                                <label class="block cursor-pointer">
                                    <input
                                        type="radio"
                                        name="wallet_id"
                                        value=""
                                        class="peer sr-only"
                                        @checked(! $hasSelectedWallet)
                                    >
                                    <span class="flex items-center justify-between gap-3 rounded-xl border border-brand-ink/15 bg-white px-4 py-3 text-sm font-bold text-brand-ink transition peer-checked:border-brand-teal peer-checked:ring-2 peer-checked:ring-brand-teal/30">
                                        <span>بدون محفظة</span>
                                        <span class="text-xs font-bold text-slate-500">متابعة بدون تحديد</span>
                                    </span>
                                </label>

                                @foreach ($wallets as $wallet)
                                    <label class="block cursor-pointer">
                                        <input
                                            type="radio"
                                            name="wallet_id"
                                            value="{{ $wallet->id }}"
                                            class="peer sr-only"
                                            @checked($selectedWalletId === (string) $wallet->id)
                                        >
                                        <span class="flex items-center gap-3 rounded-xl border border-brand-ink/15 bg-white px-4 py-3 transition peer-checked:border-brand-teal peer-checked:ring-2 peer-checked:ring-brand-teal/30">
                                            @if ($wallet->icon_public_url)
                                                <img
                                                    src="{{ $wallet->icon_public_url }}"
                                                    alt=""
                                                    class="h-9 w-9 shrink-0 rounded-lg object-contain"
                                                    loading="lazy"
                                                >
                                            @endif

                                            <span class="min-w-0 flex-1">
                                                <span class="block text-sm font-extrabold text-brand-ink">{{ $wallet->name }}</span>
                                                <span class="mt-0.5 block truncate text-xs text-slate-500" dir="ltr">{{ $wallet->account_identifier }}</span>
                                            </span>
                                        </span>
                                    </label>
                                @endforeach
                            </fieldset>

                            @error('wallet_id')
                                <p class="text-xs font-bold text-danger">{{ $message }}</p>
                            @enderror

                            <p class="text-xs leading-6 text-slate-500">
                                اختيار المحفظة يحدد وسيلة التحويل المفضلة فقط، ولا يُعد تأكيدًا للدفع. يبقى الطلب غير مدفوع حتى يتحقق المتجر من التحويل.
                            </p>
                        </div>
                    </section>

                    <section class="card" aria-labelledby="notes-heading">
                        <div class="flex items-start gap-3 border-b border-brand-ink/10 p-5">
                            <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-brand-teal/20 text-sm font-extrabold text-brand-ink" aria-hidden="true">4</span>
                            <div>
                                <h2 id="notes-heading" class="text-lg font-extrabold text-brand-ink">ملاحظات إضافية (اختياري)</h2>
                                <p class="mt-1 text-sm leading-7 text-slate-600">
                                    أي تفاصيل تريد إضافتها إلى الطلب.
                                </p>
                            </div>
                        </div>

                        <div class="p-5">
                            <x-form-field label="الملاحظات" name="notes" hint="لا تشارك كلمات المرور أو بيانات بطاقات الدفع.">
                                <textarea
                                    id="notes"
                                    name="notes"
                                    rows="3"
                                    maxlength="{{ (int) config('checkout.notes_max', 2000) }}"
                                    aria-describedby="notes-hint"
                                    class="input @error('notes') border-danger @enderror"
                                >{{ old('notes', $draft['notes'] ?? '') }}</textarea>
                            </x-form-field>
                        </div>
                    </section>

                    <section class="card" aria-labelledby="review-heading">
                        <div class="flex items-start gap-3 border-b border-brand-ink/10 p-5">
                            <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-brand-teal/20 text-sm font-extrabold text-brand-ink" aria-hidden="true">5</span>
                            <div>
                                <h2 id="review-heading" class="text-lg font-extrabold text-brand-ink">مراجعة المنتجات</h2>
                                <p class="mt-1 text-sm leading-7 text-slate-600">
                                    تأكد من المنتجات والكميات قبل تأكيد الطلب.
                                </p>
                            </div>
                        </div>

                        <ul class="divide-y divide-brand-ink/10 px-5">
                            @foreach ($summary['lines'] as $line)
                                @php
                                    $product = $line['product'];
                                    $image = $product?->images->first();
                                @endphp

                                <li class="flex items-center gap-3 py-4">
                                    <div class="h-16 w-16 shrink-0 overflow-hidden rounded-lg bg-surface-muted">
                                        @if ($image?->public_url)
                                            <img
                                                src="{{ $image->public_url }}"
                                                alt="{{ $image->alt_text ?: $product?->name }}"
                                                class="h-full w-full object-cover"
                                                loading="lazy"
                                            >
                                        @else
                                            <span class="grid h-full place-items-center text-slate-400" aria-hidden="true">
                                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                                    <rect x="3" y="4" width="18" height="16" rx="2"></rect>
                                                    <circle cx="9" cy="10" r="1.5"></circle>
                                                    <path d="m21 15-5-5L5 20"></path>
                                                </svg>
                                            </span>
                                        @endif
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <p class="line-clamp-2 text-sm font-extrabold text-brand-ink">{{ $product?->name }}</p>
                                        <p class="mt-1 text-xs text-slate-500">
                                            الكمية: {{ $line['quantity'] }}
                                            @if ($line['unit_price_cents'] !== null)
                                                × <span dir="ltr">{{ $money($line['unit_price_cents']) }} {{ $currency }}</span>
                                            @endif
                                        </p>
                                    </div>

                                    <p class="text-sm font-extrabold text-brand-ink" dir="ltr">
                                        {{ $money($line['line_total_cents']) }} {{ $currency }}
                                    </p>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                </div>

                <aside aria-label="ملخص الطلب">
                    <div class="card space-y-4 p-5 lg:sticky lg:top-28">
                        <h2 class="text-lg font-extrabold text-brand-ink">ملخص الطلب</h2>

                        <dl class="space-y-2 text-sm">
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-slate-600">عدد القطع</dt>
                                <dd class="font-bold text-brand-ink">{{ $summary['quantity'] }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-slate-600">المجموع الفرعي</dt>
                                <dd class="font-bold text-brand-ink" dir="ltr">{{ $money($summary['subtotal_cents']) }} {{ $currency }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-3 border-t border-brand-ink/10 pt-3">
                                <dt class="font-bold text-brand-ink">الإجمالي</dt>
                                <dd class="text-lg font-extrabold text-brand-ink" dir="ltr">{{ $money($summary['subtotal_cents']) }} {{ $currency }}</dd>
                            </div>
                        </dl>

                        <p class="text-xs leading-6 text-slate-500">
                            لا تُضاف أي رسوم غير مسجلة. الإجمالي المعروض هو مجموع أسعار المنتجات الحالية.
                        </p>

                        <div class="rounded-xl border border-warning/25 bg-warning/10 px-4 py-3 text-xs font-bold leading-6 text-warning">
                            الأسعار المعروضة حالية. راجعها قبل تأكيد الطلب؛ قد تتغير الأسعار قبل إنشاء الطلب.
                        </div>

                        <button type="submit" class="btn btn-primary w-full">
                            تأكيد الطلب
                        </button>

                        <a href="{{ route('cart.index') }}" class="btn btn-outline w-full">العودة إلى السلة</a>
                    </div>
                </aside>
            </div>
        </form>
    </div>
@endsection
