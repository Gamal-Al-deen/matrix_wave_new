@extends('layouts.app')

@section('title', 'طلباتي')
@section('meta_description', 'راجع طلباتك السابقة وحالتها وتفاصيلها من حسابك.')

@section('content')
    <div class="container-page py-7 sm:py-10">
        <x-breadcrumbs :items="[
            ['label' => 'الرئيسية', 'url' => route('home')],
            ['label' => 'طلباتي'],
        ]" />

        <x-section-heading
            title="طلباتي"
            description="تظهر هنا طلباتك فقط. افتح أي طلب لمراجعة تفاصيله وحالته."
            level="h1"
        />

        @if ($orderNotifications->isNotEmpty())
            <section class="card mb-6 overflow-hidden" aria-labelledby="order-notifications-heading">
                <div class="flex items-center justify-between gap-3 border-b border-brand-ink/10 p-4 sm:px-5">
                    <h2 id="order-notifications-heading" class="flex items-center gap-2 text-base font-extrabold text-brand-ink">
                        <span class="grid h-9 w-9 place-items-center rounded-xl bg-brand-teal/15 text-brand-ink">
                            <x-icon name="bell" class="h-5 w-5" />
                        </span>
                        تحديثات طلباتك
                    </h2>
                    <a href="{{ route('notifications.index') }}" class="text-xs font-bold text-brand-teal underline-offset-4 hover:underline">كل الإشعارات</a>
                </div>
                <ul class="divide-y divide-brand-ink/10">
                    @foreach ($orderNotifications as $notification)
                        @php($unread = $notification->read_at === null)
                        <li>
                            <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                                @csrf
                                <button type="submit" class="flex w-full items-start gap-3 p-4 text-start transition hover:bg-surface-muted/70 sm:px-5">
                                    <span @class([
                                        'mt-0.5 grid h-8 w-8 shrink-0 place-items-center rounded-lg',
                                        'bg-brand-teal/15 text-brand-ink' => $unread,
                                        'bg-surface-muted text-slate-500' => ! $unread,
                                    ])>
                                        <x-icon name="orders" class="h-4 w-4" />
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="flex flex-wrap items-center gap-2">
                                            <span class="text-sm font-extrabold text-brand-ink">{{ data_get($notification->data, 'title', 'تحديث حالة الطلب') }}</span>
                                            @if ($unread)
                                                <span class="rounded-full bg-brand-teal/15 px-2 py-0.5 text-[0.65rem] font-extrabold text-brand-ink">جديد</span>
                                            @endif
                                        </span>
                                        <span class="mt-1 block text-sm leading-6 text-slate-600">{{ data_get($notification->data, 'body') }}</span>
                                        <span class="mt-1.5 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                                            <x-icon name="clock" class="h-3.5 w-3.5" />
                                            <time datetime="{{ $notification->created_at->toIso8601String() }}">{{ $notification->created_at->diffForHumans() }}</time>
                                            <span aria-hidden="true">·</span>
                                            <span dir="ltr">{{ data_get($notification->data, 'order_number') }}</span>
                                        </span>
                                    </span>
                                    <x-icon name="chevron-left" class="mt-1 h-4 w-4 shrink-0 text-slate-400" />
                                </button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if ($orders->isEmpty())
            <x-empty-state
                title="لا توجد طلبات بعد"
                description="لم تقم بإتمام أي طلب حتى الآن. تصفّح المنتجات وأضف ما يناسبك إلى السلة."
                action-label="تصفّح المنتجات"
                action-icon="box"
                :action-url="route('products.index')"
            />
        @else
            <ul class="space-y-3.5" aria-label="قائمة طلباتك">
                @foreach ($orders as $order)
                    <li>
                        <article class="card card-hover p-4 sm:p-5">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                <div class="min-w-0">
                                    <p class="flex items-center gap-1.5 text-xs font-bold text-slate-500">
                                        <x-icon name="orders" class="h-4 w-4 text-brand-teal" />
                                        رقم الطلب
                                    </p>
                                    <p class="mt-1 break-all text-lg font-extrabold text-brand-ink" dir="ltr">{{ $order->order_number }}</p>
                                    <p class="mt-2 flex items-center gap-1.5 text-xs text-slate-500">
                                        <x-icon name="calendar" class="h-3.5 w-3.5 text-slate-400" />
                                        <span>تاريخ الطلب:</span>
                                        <time datetime="{{ $order->created_at->toIso8601String() }}" dir="ltr">{{ $order->created_at->format('Y-m-d H:i') }}</time>
                                    </p>
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    <x-order-status-badge :status="$order->status" />
                                    <x-payment-status-badge :status="$order->payment_status" />
                                </div>
                            </div>

                            <dl class="mt-4 grid grid-cols-2 gap-2 border-t border-brand-ink/10 pt-4 text-sm sm:grid-cols-3">
                                <div class="rounded-xl bg-surface-muted p-3">
                                    <dt class="flex items-center gap-1.5 text-xs text-slate-500">
                                        <x-icon name="box" class="h-3.5 w-3.5 text-brand-teal" />
                                        عدد المنتجات
                                    </dt>
                                    <dd class="mt-1 font-extrabold text-brand-ink">{{ $order->items_count }}</dd>
                                </div>
                                <div class="rounded-xl bg-surface-muted p-3">
                                    <dt class="flex items-center gap-1.5 text-xs text-slate-500">
                                        <x-icon name="wallet" class="h-3.5 w-3.5 text-brand-teal" />
                                        إجمالي الطلب
                                    </dt>
                                    <dd class="mt-1 font-extrabold text-brand-ink" dir="ltr">{{ number_format((float) $order->total, 2) }} {{ $order->currency_code }}</dd>
                                </div>
                                <div class="rounded-xl bg-surface-muted p-3">
                                    <dt class="flex items-center gap-1.5 text-xs text-slate-500">
                                        <x-icon name="credit-card" class="h-3.5 w-3.5 text-brand-teal" />
                                        حالة الدفع
                                    </dt>
                                    <dd class="mt-1 font-bold text-brand-ink">{{ $order->payment_status->label() }}</dd>
                                </div>
                            </dl>

                            <div class="mt-4 flex justify-end">
                                <a
                                    href="{{ route('orders.show', $order) }}"
                                    class="btn btn-outline w-full sm:w-auto"
                                    aria-label="عرض تفاصيل الطلب رقم {{ $order->order_number }}"
                                >
                                    <x-icon name="eye" class="h-4 w-4" />
                                    عرض تفاصيل الطلب
                                </a>
                            </div>
                        </article>
                    </li>
                @endforeach
            </ul>

            <div class="mt-8">{{ $orders->links() }}</div>
        @endif
    </div>
@endsection
