@extends('layouts.admin')

@section('title', 'الطلبات')

@section('content')
    @php
        $hasFilters = $search !== '' || $status !== null || $paymentStatus !== null;
        $emptyTitle = $hasFilters ? 'لا توجد طلبات مطابقة' : 'لا توجد طلبات بعد';
        $emptyDescription = $hasFilters
            ? 'جرّب تعديل كلمة البحث أو اختيار حالات أخرى، أو امسح عوامل التصفية لعرض كل الطلبات.'
            : 'لم يُنشئ أي عميل طلبًا حتى الآن. ستظهر الطلبات الجديدة هنا تلقائيًا.';
    @endphp

    <div class="space-y-6">
        <x-breadcrumbs :items="[
            ['label' => 'لوحة التحكم', 'url' => route('admin.dashboard')],
            ['label' => 'الطلبات'],
        ]" />

        <x-section-heading
            title="الطلبات"
            description="ابحث في الطلبات وصفّها حسب حالة التسليم أو حالة الدفع، ثم افتح الطلب لمراجعة بياناته المحفوظة وإدارة حالته."
        />

        <form method="GET" action="{{ route('admin.orders.index') }}" class="card p-4 sm:p-5" role="search" aria-label="تصفية الطلبات">
            <div class="grid gap-4 lg:grid-cols-[minmax(0,1.5fr)_minmax(0,1fr)_minmax(0,1fr)_auto] lg:items-end">
                <x-form-field label="بحث" name="q" hint="رقم الطلب أو اسم العميل أو الهاتف أو البريد الإلكتروني.">
                    <input
                        id="q"
                        name="q"
                        type="search"
                        value="{{ $search }}"
                        maxlength="100"
                        class="input"
                        placeholder="ابحث برقم الطلب أو بيانات العميل"
                    >
                </x-form-field>

                <x-form-field label="حالة الطلب" name="status">
                    <select id="status" name="status" class="input">
                        <option value="">كل الحالات</option>
                        @foreach (\App\Enums\OrderStatus::cases() as $case)
                            <option value="{{ $case->value }}" @selected($status === $case)>{{ $case->label() }}</option>
                        @endforeach
                    </select>
                </x-form-field>

                <x-form-field label="حالة الدفع" name="payment_status">
                    <select id="payment_status" name="payment_status" class="input">
                        <option value="">كل الحالات</option>
                        @foreach (\App\Enums\PaymentStatus::cases() as $case)
                            <option value="{{ $case->value }}" @selected($paymentStatus === $case)>{{ $case->label() }}</option>
                        @endforeach
                    </select>
                </x-form-field>

                <div class="flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-primary">تصفية</button>
                    <a href="{{ route('admin.orders.index') }}" class="btn btn-outline">مسح</a>
                </div>
            </div>
        </form>

        @if ($orders->isEmpty())
            <x-empty-state
                :title="$emptyTitle"
                :description="$emptyDescription"
                :action-label="$hasFilters ? 'مسح عوامل التصفية' : null"
                :action-url="$hasFilters ? route('admin.orders.index') : null"
            />
        @else
            <div class="card overflow-hidden">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-brand-ink/10 px-4 py-3">
                    <p class="text-xs font-bold text-slate-600">{{ $orders->total() }} طلب</p>
                    <p class="text-xs text-slate-500">الأحدث أولًا</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="responsive-table w-full min-w-[900px] text-sm">
                        <thead class="bg-surface-muted text-xs font-bold text-slate-600">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-start">الطلب</th>
                                <th scope="col" class="px-4 py-3 text-start">العميل</th>
                                <th scope="col" class="px-4 py-3 text-start">الإجمالي</th>
                                <th scope="col" class="px-4 py-3 text-start">حالة الطلب</th>
                                <th scope="col" class="px-4 py-3 text-start">حالة الدفع</th>
                                <th scope="col" class="px-4 py-3 text-start">إجراء</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-brand-ink/5">
                            @foreach ($orders as $order)
                                <tr>
                                    <td class="px-4 py-3" data-label="الطلب">
                                        <div class="min-w-0">
                                            <p class="break-all font-extrabold text-brand-ink" dir="ltr">{{ $order->order_number }}</p>
                                            <p class="mt-1 text-xs text-slate-500">
                                                <time datetime="{{ $order->created_at->toIso8601String() }}" dir="ltr">{{ $order->created_at->format('Y-m-d H:i') }}</time>
                                            </p>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3" data-label="العميل">
                                        <div class="min-w-0">
                                            <p class="font-bold text-brand-ink">{{ $order->customer_name_snapshot }}</p>
                                            <p class="mt-1 text-xs text-slate-500" dir="ltr">{{ $order->customer_phone_snapshot }}</p>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 font-bold text-brand-ink" data-label="الإجمالي">
                                        <span dir="ltr">{{ number_format((float) $order->total, 2) }} {{ $order->currency_code }}</span>
                                    </td>
                                    <td class="px-4 py-3" data-label="حالة الطلب">
                                        <x-order-status-badge :status="$order->status" />
                                    </td>
                                    <td class="px-4 py-3" data-label="حالة الدفع">
                                        <x-payment-status-badge :status="$order->payment_status" />
                                    </td>
                                    <td class="px-4 py-3" data-label="إجراء">
                                        <a href="{{ route('admin.orders.show', $order) }}" class="table-action gap-1.5 text-brand-ink hover:bg-brand-teal/10 hover:underline">
                                            <x-icon name="eye" class="h-4 w-4" />
                                            عرض التفاصيل
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-brand-ink/10 px-4 py-3">
                    {{ $orders->links() }}
                </div>
            </div>
        @endif
    </div>
@endsection
