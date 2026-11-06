@extends('layouts.admin')

@section('title', 'تفاصيل الطلب')

@section('content')
    @php
        $money = fn (string $amount): string => number_format((float) $amount, 2).' '.$order->currency_code;
        $totalQuantity = $order->items->sum('quantity');
        $hasDeliveryFee = (float) $order->delivery_fee > 0;
        $allowedTransitions = $order->status->allowedTransitions();
        $timeline = collect([
            'تاريخ التأكيد' => $order->confirmed_at,
            'تاريخ الاكتمال' => $order->completed_at,
            'تاريخ الإلغاء' => $order->cancelled_at,
            'أول محاولة واتساب' => $order->whatsapp_opened_at,
        ])->filter();
    @endphp

    <div class="space-y-6">
        <x-breadcrumbs :items="[
            ['label' => 'لوحة التحكم', 'url' => route('admin.dashboard')],
            ['label' => 'الطلبات', 'url' => route('admin.orders.index')],
            ['label' => $order->order_number],
        ]" />

        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-bold text-slate-500">رقم الطلب</p>
                <h2 class="mt-1 break-all text-2xl font-extrabold text-brand-ink" dir="ltr">{{ $order->order_number }}</h2>
                <p class="mt-2 text-sm text-slate-600">
                    أُنشئ في
                    <time datetime="{{ $order->created_at->toIso8601String() }}" dir="ltr">{{ $order->created_at->format('Y-m-d H:i') }}</time>
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <x-order-status-badge :status="$order->status" />
                <x-payment-status-badge :status="$order->payment_status" />
                <a href="{{ route('orders.invoice', $order) }}" class="btn btn-outline">عرض الفاتورة</a>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_380px]">
            <div class="space-y-6">
                <section class="card" aria-labelledby="order-items-heading">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-brand-ink/10 p-5">
                        <div>
                            <h2 id="order-items-heading" class="text-lg font-extrabold text-brand-ink">منتجات الطلب</h2>
                            <p class="mt-1 text-xs text-slate-500">{{ $totalQuantity }} قطعة ضمن {{ $order->items->count() }} منتج</p>
                        </div>
                        <p class="text-xs text-slate-500">الأسماء والأسعار محفوظة عند إنشاء الطلب</p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="responsive-table w-full min-w-[640px] text-sm">
                            <thead class="bg-surface-muted text-xs font-bold text-slate-600">
                                <tr>
                                    <th scope="col" class="px-5 py-3 text-start">المنتج</th>
                                    <th scope="col" class="px-5 py-3 text-start">الكمية</th>
                                    <th scope="col" class="px-5 py-3 text-start">سعر الوحدة</th>
                                    <th scope="col" class="px-5 py-3 text-start">الإجمالي</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-brand-ink/5">
                                @foreach ($order->items as $item)
                                    <tr>
                                        <td class="px-5 py-4 font-extrabold text-brand-ink" data-label="المنتج">{{ $item->product_name_snapshot }}</td>
                                        <td class="px-5 py-4 text-slate-700" data-label="الكمية">{{ $item->quantity }}</td>
                                        <td class="px-5 py-4 text-slate-700" data-label="سعر الوحدة">
                                            <span dir="ltr">{{ $money($item->unit_price) }}</span>
                                        </td>
                                        <td class="px-5 py-4 font-bold text-brand-ink" data-label="الإجمالي">
                                            <span dir="ltr">{{ $money($item->line_total) }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="card" aria-labelledby="order-delivery-heading">
                    <div class="border-b border-brand-ink/10 p-5">
                        <h2 id="order-delivery-heading" class="text-lg font-extrabold text-brand-ink">بيانات العميل والتوصيل</h2>
                        <p class="mt-1 text-xs leading-6 text-slate-500">هذه القيم محفوظة كما أُدخلت عند إنشاء الطلب.</p>
                    </div>

                    <dl class="grid gap-4 p-5 sm:grid-cols-2">
                        <div>
                            <dt class="text-xs font-bold text-slate-500">اسم العميل</dt>
                            <dd class="mt-1 font-bold text-brand-ink">{{ $order->customer_name_snapshot }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-bold text-slate-500">رقم الهاتف</dt>
                            <dd class="mt-1 font-bold text-brand-ink" dir="ltr">{{ $order->customer_phone_snapshot }}</dd>
                        </div>
                        @if ($order->customer_email_snapshot)
                            <div class="sm:col-span-2">
                                <dt class="text-xs font-bold text-slate-500">البريد الإلكتروني</dt>
                                <dd class="mt-1 break-all font-bold text-brand-ink" dir="ltr">{{ $order->customer_email_snapshot }}</dd>
                            </div>
                        @endif
                        <div class="sm:col-span-2">
                            <dt class="text-xs font-bold text-slate-500">عنوان التوصيل</dt>
                            <dd class="mt-1 whitespace-pre-line leading-7 text-brand-ink">{{ $order->delivery_address_snapshot }}</dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-xs font-bold text-slate-500">الحساب المرتبط</dt>
                            <dd class="mt-1 break-all text-brand-ink" dir="ltr">{{ $order->user?->email ?? 'غير متاح' }}</dd>
                        </div>
                    </dl>
                </section>

                @if ($order->notes)
                    <section class="card" aria-labelledby="order-notes-heading">
                        <div class="border-b border-brand-ink/10 p-5">
                            <h2 id="order-notes-heading" class="text-lg font-extrabold text-brand-ink">ملاحظات العميل</h2>
                        </div>
                        <p class="whitespace-pre-line p-5 text-sm leading-7 text-brand-ink">{{ $order->notes }}</p>
                    </section>
                @endif

                <section class="card" aria-labelledby="status-history-heading">
                    <div class="border-b border-brand-ink/10 p-5">
                        <h2 id="status-history-heading" class="text-lg font-extrabold text-brand-ink">سجل حالات الطلب</h2>
                        <p class="mt-1 text-xs leading-6 text-slate-500">كل تغيير في حالة الطلب يُسجّل هنا مع المسؤول والملاحظة إن وُجدت.</p>
                    </div>

                    <ol class="divide-y divide-brand-ink/5 px-5">
                        @forelse ($order->statusHistory as $history)
                            <li class="py-4">
                                <div class="flex flex-wrap items-center gap-2 text-sm">
                                    @if ($history->from_status)
                                        <x-order-status-badge :status="$history->from_status" />
                                        <span class="text-slate-400" aria-hidden="true">←</span>
                                    @else
                                        <span class="text-xs font-bold text-slate-500">إنشاء الطلب</span>
                                        <span class="text-slate-400" aria-hidden="true">←</span>
                                    @endif
                                    <x-order-status-badge :status="$history->to_status" />
                                </div>

                                <p class="mt-2 text-xs text-slate-500">
                                    بواسطة: <span class="font-bold text-slate-700">{{ $history->changedBy?->name ?? 'غير متاح' }}</span>
                                    <span aria-hidden="true">·</span>
                                    <time datetime="{{ $history->created_at?->toIso8601String() }}" dir="ltr">{{ $history->created_at?->format('Y-m-d H:i') }}</time>
                                </p>

                                @if ($history->note)
                                    <p class="mt-2 whitespace-pre-line rounded-xl bg-surface-muted px-3 py-2 text-xs leading-6 text-slate-700">{{ $history->note }}</p>
                                @endif
                            </li>
                        @empty
                            <li class="py-6 text-center text-sm text-slate-500">لا يوجد سجل حالات لهذا الطلب.</li>
                        @endforelse
                    </ol>
                </section>
            </div>

            <aside class="space-y-6" aria-label="إدارة الطلب وملخصه">
                <section class="card p-5" aria-labelledby="status-update-heading">
                    <h2 id="status-update-heading" class="text-lg font-extrabold text-brand-ink">تحديث حالة الطلب</h2>
                    <p class="mt-1 text-sm leading-7 text-slate-600">
                        تتحرك الحالة للأمام فقط، ويمكن إلغاء أي طلب غير مكتمل أو ملغي.
                    </p>

                    @if (empty($allowedTransitions))
                        <div class="mt-4">
                            <x-alert type="info" message="الطلب في حالة نهائية، ولا توجد انتقالات متاحة." />
                        </div>
                    @else
                        <form method="POST" action="{{ route('admin.orders.status', $order) }}" class="mt-4 space-y-4" data-submit-once>
                            @csrf
                            @method('PATCH')

                            <x-form-field label="الحالة الجديدة" name="status" required>
                                <select id="status" name="status" class="input" required>
                                    @foreach ($allowedTransitions as $nextStatus)
                                        <option value="{{ $nextStatus->value }}" @selected(old('status') === $nextStatus->value)>
                                            {{ $nextStatus->label() }}
                                        </option>
                                    @endforeach
                                </select>
                            </x-form-field>

                            <x-form-field label="ملاحظة التغيير" name="note" hint="اختيارية، وتظهر في سجل الحالات. الحد الأقصى 2000 حرف.">
                                <textarea id="note" name="note" rows="3" maxlength="2000" class="input">{{ old('note') }}</textarea>
                            </x-form-field>

                            <div class="rounded-xl border border-warning/25 bg-warning/10 px-3 py-2 text-xs leading-6 text-warning">
                                اختيار «ملغي» يثبت الإلغاء، ولا يمكن إعادة فتح الطلب أو تغيير حالته بعد ذلك.
                            </div>

                            <button type="submit" class="btn btn-secondary w-full">تحديث حالة الطلب</button>
                        </form>
                    @endif

                    <p class="mt-4 text-xs leading-6 text-slate-500">تغيير حالة الطلب لا يغيّر حالة الدفع تلقائيًا.</p>
                </section>

                <section class="card p-5" aria-labelledby="payment-update-heading">
                    <h2 id="payment-update-heading" class="text-lg font-extrabold text-brand-ink">تحديث حالة الدفع</h2>
                    <p class="mt-1 text-sm leading-7 text-slate-600">
                        حالة الدفع منفصلة عن حالة الطلب. اختر الحالة الفعلية بعد التحقق من التحويل يدويًا.
                    </p>

                    <form method="POST" action="{{ route('admin.orders.payment-status', $order) }}" class="mt-4 space-y-4" data-submit-once>
                        @csrf
                        @method('PATCH')

                        <x-form-field label="حالة الدفع" name="payment_status" required>
                            <select id="payment_status" name="payment_status" class="input" required>
                                @foreach ($paymentStatuses as $paymentStatus)
                                    <option value="{{ $paymentStatus->value }}" @selected(old('payment_status', $order->payment_status->value) === $paymentStatus->value)>
                                        {{ $paymentStatus->label() }}
                                    </option>
                                @endforeach
                            </select>
                        </x-form-field>

                        <button type="submit" class="btn btn-primary w-full">تحديث حالة الدفع</button>
                    </form>

                    <p class="mt-4 text-xs leading-6 text-slate-500">
                        لا يغيّر هذا الإجراء حالة الطلب، ولا ينفّذ عملية دفع أو استرداد. لا يوجد ربط ببوابة دفع.
                    </p>
                </section>

                <section class="card p-5" aria-labelledby="order-summary-heading">
                    <h2 id="order-summary-heading" class="text-lg font-extrabold text-brand-ink">ملخص الطلب</h2>

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

                    @unless ($hasDeliveryFee)
                        <p class="mt-4 text-xs leading-6 text-slate-500">لم تُسجّل رسوم توصيل على هذا الطلب.</p>
                    @endunless

                    <dl class="mt-5 space-y-3 border-t border-brand-ink/10 pt-4 text-sm">
                        <div>
                            <dt class="text-xs font-bold text-slate-500">المحفظة المختارة</dt>
                            <dd class="mt-1 font-bold text-brand-ink">{{ $order->wallet?->name ?? 'بدون محفظة' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-bold text-slate-500">العملة المحفوظة</dt>
                            <dd class="mt-1 font-bold text-brand-ink" dir="ltr">{{ $order->currency_code }}</dd>
                        </div>
                    </dl>

                    @if ($timeline->isNotEmpty())
                        <dl class="mt-5 space-y-3 border-t border-brand-ink/10 pt-4 text-sm">
                            @foreach ($timeline as $label => $timestamp)
                                <div class="flex items-start justify-between gap-3">
                                    <dt class="text-xs font-bold text-slate-500">{{ $label }}</dt>
                                    <dd class="text-xs text-slate-700">
                                        <time datetime="{{ $timestamp->toIso8601String() }}" dir="ltr">{{ $timestamp->format('Y-m-d H:i') }}</time>
                                    </dd>
                                </div>
                            @endforeach
                        </dl>
                    @endif

                    <a href="{{ route('admin.orders.index') }}" class="btn btn-outline mt-5 w-full">العودة إلى الطلبات</a>
                </section>
            </aside>
        </div>
    </div>
@endsection
