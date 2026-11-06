@extends('layouts.invoice')

@section('title', 'فاتورة الطلب '.$order->order_number)

@section('content')
    @php
        $money = fn (string $amount): string => number_format((float) $amount, 2).' '.$order->currency_code;
        $totalQuantity = $order->items->sum('quantity');
        $hasDeliveryFee = (float) $order->delivery_fee > 0;
        $backUrl = auth()->user()?->isAdmin()
            ? route('admin.orders.show', $order)
            : route('orders.show', $order);
    @endphp

    <div class="no-print mb-6 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ $backUrl }}" class="btn btn-outline w-full sm:w-auto">
            <x-icon name="arrow-right" class="h-4 w-4" />
            العودة إلى الطلب
        </a>

        <div class="flex w-full flex-wrap gap-2 sm:w-auto">
            <a href="{{ route('orders.invoice.image', $order) }}" class="btn btn-outline flex-1 sm:flex-none" download>
                <x-icon name="download" class="h-4 w-4" />
                تنزيل كصورة SVG
            </a>
            <button type="button" class="btn btn-secondary flex-1 sm:flex-none" data-print-invoice>
                <x-icon name="printer" class="h-4 w-4" />
                طباعة / حفظ PDF
            </button>
        </div>
    </div>

    <article id="invoice-document" class="invoice-document rounded-2xl border border-brand-ink/10 p-6 sm:p-8" aria-labelledby="invoice-heading">
        <div class="flex flex-col gap-5 border-b border-brand-ink/10 pb-6 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="text-xs font-bold text-slate-500">رقم الفاتورة (رقم الطلب)</p>
                <h1 id="invoice-heading" class="mt-1 break-all text-2xl font-extrabold text-brand-ink" dir="ltr">{{ $order->order_number }}</h1>
                <p class="mt-2 text-sm text-slate-600">
                    تاريخ الإصدار:
                    <time datetime="{{ $order->created_at->toIso8601String() }}" dir="ltr">{{ $order->created_at->format('Y-m-d H:i') }}</time>
                </p>
            </div>

            <div class="sm:text-end">
                <p class="text-xs font-bold text-slate-500">حالة الدفع</p>
                <div class="mt-2">
                    <x-payment-status-badge :status="$order->payment_status" />
                </div>
                <p class="mt-2 text-xs text-slate-500">العملة: <span dir="ltr">{{ $order->currency_code }}</span></p>
            </div>
        </div>

        <div class="grid gap-6 border-b border-brand-ink/10 py-6 sm:grid-cols-2">
            <section aria-labelledby="invoice-customer-heading">
                <h2 id="invoice-customer-heading" class="text-sm font-extrabold text-brand-ink">بيانات العميل</h2>
                <dl class="mt-3 space-y-2 text-sm">
                    <div>
                        <dt class="text-xs font-bold text-slate-500">الاسم</dt>
                        <dd class="mt-0.5 font-bold text-brand-ink">{{ $order->customer_name_snapshot }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold text-slate-500">رقم الهاتف</dt>
                        <dd class="mt-0.5 font-bold text-brand-ink" dir="ltr">{{ $order->customer_phone_snapshot }}</dd>
                    </div>
                    @if ($order->customer_email_snapshot)
                        <div>
                            <dt class="text-xs font-bold text-slate-500">البريد الإلكتروني</dt>
                            <dd class="mt-0.5 break-all font-bold text-brand-ink" dir="ltr">{{ $order->customer_email_snapshot }}</dd>
                        </div>
                    @endif
                </dl>
            </section>

            <section aria-labelledby="invoice-delivery-heading">
                <h2 id="invoice-delivery-heading" class="text-sm font-extrabold text-brand-ink">عنوان التوصيل</h2>
                <p class="mt-3 whitespace-pre-line text-sm leading-7 text-brand-ink">{{ $order->delivery_address_snapshot }}</p>
            </section>
        </div>

        <section class="py-6" aria-labelledby="invoice-items-heading">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 id="invoice-items-heading" class="text-sm font-extrabold text-brand-ink">تفاصيل المنتجات</h2>
                <p class="text-xs text-slate-500">{{ $totalQuantity }} قطعة ضمن {{ $order->items->count() }} منتج</p>
            </div>

            <div class="mt-4 overflow-x-auto">
                <table class="responsive-table w-full min-w-[620px] text-sm">
                    <thead class="bg-surface-muted text-xs font-bold text-slate-600">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-start">المنتج</th>
                            <th scope="col" class="px-4 py-3 text-start">الكمية</th>
                            <th scope="col" class="px-4 py-3 text-start">سعر الوحدة</th>
                            <th scope="col" class="px-4 py-3 text-start">الإجمالي</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-brand-ink/5">
                        @forelse ($order->items as $item)
                            <tr>
                                <td class="px-4 py-3 font-bold text-brand-ink" data-label="المنتج">{{ $item->product_name_snapshot }}</td>
                                <td class="px-4 py-3 text-slate-700" data-label="الكمية">{{ $item->quantity }}</td>
                                <td class="px-4 py-3 text-slate-700" data-label="سعر الوحدة">
                                    <span dir="ltr">{{ $money($item->unit_price) }}</span>
                                </td>
                                <td class="px-4 py-3 font-bold text-brand-ink" data-label="الإجمالي">
                                    <span dir="ltr">{{ $money($item->line_total) }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-6 text-center text-slate-500" data-label="">لا توجد منتجات محفوظة في هذا الطلب.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <div class="flex flex-col gap-6 border-t border-brand-ink/10 pt-6 sm:flex-row sm:items-start sm:justify-between">
            <div class="max-w-md text-xs leading-6 text-slate-500">
                <p>هذه الفاتورة مبنية على بيانات الطلب المحفوظة عند إنشائه، وتظل أسعارها وعملتها دون تغيير عند تعديل بيانات المنتجات أو إعدادات المتجر.</p>

                @if ($contactPhone || $contactEmail)
                    <div class="mt-3">
                        <p class="font-bold text-slate-700">للاستفسار:</p>
                        @if ($contactPhone)
                            <p class="mt-1" dir="ltr">{{ $contactPhone }}</p>
                        @endif
                        @if ($contactEmail)
                            <p class="mt-1 break-all" dir="ltr">{{ $contactEmail }}</p>
                        @endif
                    </div>
                @endif
            </div>

            <dl class="w-full space-y-3 text-sm sm:max-w-xs">
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-slate-600">المجموع الفرعي</dt>
                    <dd class="font-bold text-brand-ink" dir="ltr">{{ $money($order->subtotal) }}</dd>
                </div>

                @if ($hasDeliveryFee)
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-slate-600">رسوم التوصيل</dt>
                        <dd class="font-bold text-brand-ink" dir="ltr">{{ $money($order->delivery_fee) }}</dd>
                    </div>
                @endif

                <div class="flex items-center justify-between gap-4 border-t border-brand-ink/10 pt-3">
                    <dt class="font-extrabold text-brand-ink">الإجمالي</dt>
                    <dd class="text-lg font-extrabold text-brand-ink" dir="ltr">{{ $money($order->total) }}</dd>
                </div>
            </dl>
        </div>

        @unless ($hasDeliveryFee)
            <p class="mt-5 text-xs leading-6 text-slate-500">لم تُسجّل رسوم توصيل على هذا الطلب.</p>
        @endunless
    </article>
@endsection
