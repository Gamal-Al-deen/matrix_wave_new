@extends('layouts.app')

@section('title', $justCreated ? 'تأكيد الطلب' : 'تفاصيل الطلب')
@section('meta_description', 'راجع تفاصيل طلبك والمنتجات والأسعار والبيانات المحفوظة عند إنشائه.')

@section('content')
    @php
        $money = fn (string $amount): string => number_format((float) $amount, 2).' '.$order->currency_code;
        $totalQuantity = $order->items->sum('quantity');
        $hasDeliveryFee = (float) $order->delivery_fee > 0;
        $pageTitle = $justCreated ? 'تأكيد الطلب' : 'تفاصيل الطلب';
    @endphp

    <div class="container-page py-8 sm:py-12">
        <x-breadcrumbs :items="[
            ['label' => 'الرئيسية', 'url' => route('home')],
            ['label' => 'طلباتي', 'url' => route('orders.index')],
            ['label' => 'تفاصيل الطلب'],
        ]" />

        @if ($justCreated)
            <section class="mb-6 rounded-2xl border border-success/25 bg-success/10 p-5 sm:p-6" aria-labelledby="confirmation-heading">
                <div class="flex items-start gap-4">
                    <span class="grid h-12 w-12 shrink-0 place-items-center rounded-full bg-success/15 text-success" aria-hidden="true">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="m5 12 4 4L19 6"></path>
                        </svg>
                    </span>

                    <div class="min-w-0">
                        <h2 id="confirmation-heading" class="text-xl font-extrabold text-brand-ink">تم إنشاء طلبك بنجاح</h2>
                        <p class="mt-2 text-sm leading-7 text-slate-700">
                            احتفظ برقم الطلب التالي للمتابعة. طلبك مسجّل الآن بحالة «قيد الانتظار»، ويمكنك مراجعة حالته من صفحة طلباتي.
                        </p>
                        <p class="mt-3 break-all rounded-xl bg-white/70 px-3 py-2 text-base font-extrabold text-brand-ink" dir="ltr">
                            {{ $order->order_number }}
                        </p>
                        <p class="mt-2 text-sm font-bold text-brand-ink">
                            إجمالي الطلب:
                            <span dir="ltr">{{ $money($order->total) }}</span>
                        </p>
                    </div>
                </div>
            </section>
        @endif

        @if ($justCreated && $whatsappUrl)
            <span hidden data-whatsapp-autopen="{{ $whatsappUrl }}"></span>
        @endif

        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <x-section-heading
                :title="$pageTitle"
                description="هذه التفاصيل محفوظة من لحظة إنشاء الطلب، وتشمل المنتجات والأسعار وبيانات التوصيل."
            />

            <a href="{{ route('orders.index') }}" class="text-sm font-bold text-brand-ink underline decoration-brand-teal decoration-2 underline-offset-4 hover:text-brand-ink/80">
                العودة إلى طلباتي
            </a>
        </div>

        <section class="card mt-2 p-5" aria-label="بيانات الطلب">
            <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <dt class="text-xs font-bold text-slate-500">رقم الطلب</dt>
                    <dd class="mt-1 break-all font-extrabold text-brand-ink" dir="ltr">{{ $order->order_number }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-bold text-slate-500">تاريخ الطلب</dt>
                    <dd class="mt-1 font-bold text-brand-ink">
                        <time datetime="{{ $order->created_at->toIso8601String() }}" dir="ltr">{{ $order->created_at->format('Y-m-d H:i') }}</time>
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-bold text-slate-500">حالة الطلب</dt>
                    <dd class="mt-1"><x-order-status-badge :status="$order->status" /></dd>
                </div>
                <div>
                    <dt class="text-xs font-bold text-slate-500">حالة الدفع</dt>
                    <dd class="mt-1"><x-payment-status-badge :status="$order->payment_status" /></dd>
                </div>
            </dl>
        </section>

        @if ($whatsappAvailable)
            <section class="card mt-6 border-success/20 bg-success/5 p-5" aria-labelledby="whatsapp-heading">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div class="flex items-start gap-3">
                        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-success/10 text-success" aria-hidden="true">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="M21 11.5a8.4 8.4 0 0 1-8.5 8.3 8.7 8.7 0 0 1-3.6-.8L3 21l1.9-5.4a8.3 8.3 0 1 1 16.1-4.1Z"></path>
                                <path d="M8.5 9.5c.5 3 2.5 5 5.5 5.6"></path>
                            </svg>
                        </span>

                        <div>
                            <h2 id="whatsapp-heading" class="text-lg font-extrabold text-brand-ink">مشاركة تفاصيل الطلب عبر واتساب</h2>
                            <p id="whatsapp-description" class="mt-2 text-sm leading-7 text-slate-600">
                                يفتح واتساب برسالة عربية جاهزة مبنية على بيانات الطلب المحفوظة. راجعها ثم أرسلها يدويًا؛ فتح الرابط لا يعني إرسالها أو تأكيد الطلب أو الدفع.
                            </p>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('orders.whatsapp', $order) }}" target="_blank" rel="noopener noreferrer" class="shrink-0">
                        @csrf
                        <button type="submit" class="btn btn-primary w-full sm:w-auto" aria-describedby="whatsapp-description">
                            فتح واتساب برسالة جاهزة
                        </button>
                    </form>
                </div>
            </section>
        @endif

        <div class="mt-6 grid gap-6 lg:grid-cols-[minmax(0,1fr)_340px]">
            <div class="space-y-6">
                <section class="card" aria-labelledby="items-heading">
                    <div class="flex items-center justify-between gap-3 border-b border-brand-ink/10 p-5">
                        <div>
                            <h2 id="items-heading" class="text-lg font-extrabold text-brand-ink">منتجات الطلب</h2>
                            <p class="mt-1 text-xs text-slate-500">{{ $totalQuantity }} قطعة ضمن {{ $order->items->count() }} منتج</p>
                        </div>
                    </div>

                    <ul class="divide-y divide-brand-ink/10 px-5">
                        @foreach ($order->items as $item)
                            <li class="flex flex-wrap items-center gap-3 py-4 sm:flex-nowrap">
                                <span class="grid h-14 w-14 shrink-0 place-items-center rounded-lg bg-surface-muted text-slate-400" aria-hidden="true">
                                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                        <path d="M4 7.5 12 3l8 4.5v9L12 21l-8-4.5z"></path>
                                        <path d="M4 7.5 12 12l8-4.5M12 12v9"></path>
                                    </svg>
                                </span>

                                <div class="min-w-0 flex-1">
                                    <p class="line-clamp-2 text-sm font-extrabold text-brand-ink">{{ $item->product_name_snapshot }}</p>
                                    <p class="mt-1 text-xs text-slate-500">
                                        الكمية: {{ $item->quantity }}
                                        <span aria-hidden="true">·</span>
                                        سعر الوحدة:
                                        <span dir="ltr">{{ $money($item->unit_price) }}</span>
                                    </p>
                                </div>

                                <p class="w-full shrink-0 text-start text-sm font-extrabold text-brand-ink sm:w-auto sm:text-end" dir="ltr">
                                    {{ $money($item->line_total) }}
                                </p>
                            </li>
                        @endforeach
                    </ul>
                </section>

                <section class="card" aria-labelledby="delivery-heading">
                    <div class="border-b border-brand-ink/10 p-5">
                        <h2 id="delivery-heading" class="text-lg font-extrabold text-brand-ink">بيانات التوصيل</h2>
                        <p class="mt-1 text-xs leading-6 text-slate-500">هذه البيانات محفوظة كما أُدخلت عند إنشاء الطلب.</p>
                    </div>

                    <dl class="grid gap-4 p-5 sm:grid-cols-2">
                        <div class="hidden sm:block">
                            <dt class="text-xs font-bold text-slate-500">الاسم</dt>
                            <dd class="mt-1 font-bold text-brand-ink">{{ $order->customer_name_snapshot }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-bold text-slate-500">رقم الهاتف</dt>
                            <dd class="mt-1 font-bold text-brand-ink" dir="ltr">{{ $order->customer_phone_snapshot }}</dd>
                        </div>
                        @if ($order->customer_email_snapshot)
                            <div class="hidden sm:col-span-2 sm:block">
                                <dt class="text-xs font-bold text-slate-500">البريد الإلكتروني</dt>
                                <dd class="mt-1 break-all font-bold text-brand-ink" dir="ltr">{{ $order->customer_email_snapshot }}</dd>
                            </div>
                        @endif
                        <div class="sm:col-span-2">
                            <dt class="text-xs font-bold text-slate-500">عنوان التوصيل</dt>
                            <dd class="mt-1 whitespace-pre-line leading-7 text-brand-ink">{{ $order->delivery_address_snapshot }}</dd>
                        </div>
                    </dl>
                </section>

                @if ($order->notes)
                    <section class="card" aria-labelledby="notes-heading">
                        <div class="border-b border-brand-ink/10 p-5">
                            <h2 id="notes-heading" class="text-lg font-extrabold text-brand-ink">ملاحظات الطلب</h2>
                        </div>
                        <p class="whitespace-pre-line p-5 text-sm leading-7 text-brand-ink">{{ $order->notes }}</p>
                    </section>
                @endif
            </div>

            <aside aria-labelledby="summary-heading">
                <div class="card p-5 lg:sticky lg:top-28">
                    <h2 id="summary-heading" class="text-lg font-extrabold text-brand-ink">ملخص الطلب</h2>

                    <dl class="mt-4 space-y-3 text-sm">
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-slate-600">إجمالي الكمية</dt>
                            <dd class="font-bold text-brand-ink">{{ $totalQuantity }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-slate-600">المجموع الفرعي</dt>
                            <dd class="font-bold text-brand-ink" dir="ltr">{{ $money($order->subtotal) }}</dd>
                        </div>
                        @if ($hasDeliveryFee)
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-slate-600">رسوم التوصيل</dt>
                                <dd class="font-bold text-brand-ink" dir="ltr">{{ $money($order->delivery_fee) }}</dd>
                            </div>
                        @endif
                        <div class="flex items-center justify-between gap-3 border-t border-brand-ink/10 pt-3">
                            <dt class="font-bold text-brand-ink">الإجمالي</dt>
                            <dd class="text-lg font-extrabold text-brand-ink" dir="ltr">{{ $money($order->total) }}</dd>
                        </div>
                    </dl>

                    <div class="mt-4 flex flex-wrap gap-2">
                        <x-order-status-badge :status="$order->status" />
                        <x-payment-status-badge :status="$order->payment_status" />
                    </div>

                    @unless ($hasDeliveryFee)
                        <p class="mt-4 text-xs leading-6 text-slate-500">لم تُسجّل رسوم توصيل على هذا الطلب.</p>
                    @endunless

                    <p class="mt-3 text-xs leading-6 text-slate-500">
                        حالة الدفع منفصلة عن حالة الطلب، ولا تتغير تلقائيًا عند إنشاء الطلب.
                    </p>

                    <a href="{{ route('orders.invoice', $order) }}" class="btn btn-secondary mt-5 w-full">عرض الفاتورة</a>
                    <a href="{{ route('orders.index') }}" class="btn btn-outline mt-3 w-full">العودة إلى طلباتي</a>
                </div>
            </aside>
        </div>
    </div>
@endsection
