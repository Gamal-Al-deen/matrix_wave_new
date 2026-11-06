@extends('layouts.admin')

@section('title', 'تفاصيل العميل')

@section('content')
    <div class="space-y-6">
        <x-breadcrumbs :items="[
            ['label' => 'لوحة التحكم', 'url' => route('admin.dashboard')],
            ['label' => 'العملاء', 'url' => route('admin.customers.index')],
            ['label' => $customer->name],
        ]" />

        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div class="min-w-0">
                <p class="text-xs font-bold text-slate-500">حساب عميل</p>
                <h2 class="mt-1 truncate text-2xl font-extrabold text-brand-ink">{{ $customer->name }}</h2>
                <p class="mt-2 text-sm text-slate-600">
                    مسجّل منذ
                    <time datetime="{{ $customer->created_at->toIso8601String() }}" dir="ltr">{{ $customer->created_at->format('Y-m-d') }}</time>
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <span class="badge bg-brand-teal/15 text-brand-ink">{{ $customer->orders_count }} طلب</span>
                <a href="{{ route('admin.customers.index') }}" class="btn btn-outline">العودة إلى العملاء</a>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_340px]">
            <div class="space-y-6">
                <section class="card" aria-labelledby="customer-details-heading">
                    <div class="flex items-start gap-3 border-b border-brand-ink/10 p-5">
                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-brand-teal/15 text-brand-ink">
                            <x-icon name="user" class="h-5 w-5" />
                        </span>
                        <div>
                            <h2 id="customer-details-heading" class="text-lg font-extrabold text-brand-ink">بيانات العميل</h2>
                            <p class="mt-1 text-sm leading-7 text-slate-600">البيانات المحفوظة حاليًا في حساب العميل.</p>
                        </div>
                    </div>

                    <dl class="grid gap-4 p-5 sm:grid-cols-2">
                        <div>
                            <dt class="text-xs font-bold text-slate-500">الاسم الكامل</dt>
                            <dd class="mt-1 font-bold text-brand-ink">{{ $customer->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-bold text-slate-500">البريد الإلكتروني</dt>
                            <dd class="mt-1 break-all font-bold text-brand-ink" dir="ltr">{{ $customer->email }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-bold text-slate-500">رقم الهاتف</dt>
                            <dd class="mt-1 font-bold text-brand-ink" dir="ltr">
                                {{ filled($customer->phone) ? $customer->phone : '—' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-bold text-slate-500">تاريخ التسجيل</dt>
                            <dd class="mt-1 font-bold text-brand-ink">
                                <time datetime="{{ $customer->created_at->toIso8601String() }}" dir="ltr">{{ $customer->created_at->format('Y-m-d H:i') }}</time>
                            </dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-xs font-bold text-slate-500">العنوان الافتراضي</dt>
                            <dd class="mt-1 whitespace-pre-line leading-7 text-brand-ink">
                                {{ filled($customer->default_address) ? $customer->default_address : '—' }}
                            </dd>
                        </div>
                    </dl>
                </section>

                <section class="card overflow-hidden" aria-labelledby="customer-orders-heading">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-brand-ink/10 p-5">
                        <div>
                            <h2 id="customer-orders-heading" class="text-lg font-extrabold text-brand-ink">سجل الطلبات</h2>
                            <p class="mt-1 text-xs text-slate-500">{{ $orders->total() }} طلب لهذا العميل</p>
                        </div>
                        <p class="text-xs text-slate-500">الأحدث أولًا</p>
                    </div>

                    @if ($orders->isEmpty())
                        <div class="p-5">
                            <x-empty-state
                                title="لا توجد طلبات لهذا العميل"
                                description="ستظهر طلبات العميل هنا بمجرد إنشائه لأول طلب."
                            />
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="responsive-table w-full min-w-[760px] text-sm">
                                <thead class="bg-surface-muted text-xs font-bold text-slate-600">
                                    <tr>
                                        <th scope="col" class="px-4 py-3 text-start">الطلب</th>
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
                                                <p class="break-all font-extrabold text-brand-ink" dir="ltr">{{ $order->order_number }}</p>
                                                <p class="mt-1 text-xs text-slate-500">
                                                    <time datetime="{{ $order->created_at->toIso8601String() }}" dir="ltr">{{ $order->created_at->format('Y-m-d H:i') }}</time>
                                                </p>
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
                                                <a href="{{ route('admin.orders.show', $order) }}" class="table-action gap-1.5 text-brand-ink hover:underline">
                                                    <x-icon name="eye" class="h-4 w-4" />
                                                    عرض
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
                    @endif
                </section>
            </div>

            <aside class="space-y-6" aria-label="ملاحظات حول العميل">
                <section class="card p-5" aria-labelledby="customer-note-heading">
                    <h2 id="customer-note-heading" class="text-lg font-extrabold text-brand-ink">ملاحظة</h2>
                    <p class="mt-2 flex items-start gap-2 text-xs leading-6 text-slate-600">
                        <x-icon name="info" class="mt-0.5 h-4 w-4 shrink-0 text-brand-teal" />
                        الطلبات السابقة تحتفظ ببيانات العميل والعنوان والأسعار كما كانت وقت الإنشاء، حتى لو تغيّرت بيانات الحساب لاحقًا.
                    </p>
                </section>
            </aside>
        </div>
    </div>
@endsection
