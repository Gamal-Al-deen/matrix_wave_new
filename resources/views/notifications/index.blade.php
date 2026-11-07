@extends('layouts.app')

@section('title', 'الإشعارات')
@section('meta_description', 'تابع إشعارات حسابك وتحديثات طلباتك في مكان واحد.')

@section('content')
    <div class="container-page py-7 sm:py-10">
        <x-breadcrumbs :items="[
            ['label' => 'الرئيسية', 'url' => route('home')],
            ['label' => 'الإشعارات'],
        ]" />

        <x-section-heading
            title="الإشعارات"
            description="تظهر هنا إشعارات حسابك وتحديثات طلباتك. الإشعارات داخل الموقع متاحة دائمًا، ويمكنك إدارة إشعارات المتصفح من هذه الصفحة."
            level="h1"
        />

        @php
            $notificationUser = auth()->user();
        @endphp

        <section class="card mb-5 p-4 sm:p-5" aria-labelledby="notification-settings-heading" data-notification-permission>
            <div class="flex items-start gap-3">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-brand-teal/15 text-brand-ink">
                    <x-icon name="bell" class="h-5 w-5" />
                </span>
                <div class="min-w-0 flex-1">
                    <h2 id="notification-settings-heading" class="text-base font-extrabold text-brand-ink sm:text-lg">إعدادات الإشعارات</h2>
                    <p class="mt-1 text-sm leading-7 text-slate-600">
                        تحكّم في إشعارات المتصفح المنبثقة. إشعارات الموقع داخل هذه الصفحة تبقى متاحة دائمًا حتى عند تعطيل هذا الخيار.
                    </p>

                    <form method="POST" action="{{ route('notifications.settings') }}" class="mt-4 space-y-4">
                        @csrf
                        @method('PATCH')

                        <input type="hidden" name="browser_notifications_enabled" value="0">
                        <label for="browser_notifications_enabled" class="flex cursor-pointer items-start gap-3 rounded-lg border border-brand-ink/10 bg-surface-muted/60 p-3">
                            <input
                                id="browser_notifications_enabled"
                                name="browser_notifications_enabled"
                                type="checkbox"
                                value="1"
                                @checked(old('browser_notifications_enabled', $notificationUser?->browser_notifications_enabled))
                                data-browser-notifications-toggle
                                class="mt-0.5 h-5 w-5 shrink-0 rounded border-brand-ink/30 text-brand-teal focus:ring-brand-teal"
                                aria-describedby="browser-notifications-hint"
                            >
                            <span class="min-w-0">
                                <span class="block text-sm font-bold text-brand-ink">إشعارات المتصفح المنبثقة</span>
                                <span id="browser-notifications-hint" class="mt-1 block text-xs leading-6 text-slate-600">
                                    تظهر رسائل منبثقة من المتصفح عند وصول إشعار جديد، بشرط منح الإذن وفتح الموقع.
                                </span>
                            </span>
                        </label>

                        <div class="flex flex-wrap items-center gap-2">
                            <button type="submit" class="btn btn-primary gap-1.5 px-3 py-2 text-xs">
                                <x-icon name="save" class="h-4 w-4" />
                                حفظ الإعداد
                            </button>
                            <button type="button" class="btn btn-outline gap-1.5 px-3 py-2 text-xs" data-notification-permission-request>
                                <x-icon name="bell" class="h-4 w-4" />
                                تفعيل إشعارات المتصفح
                            </button>
                        </div>
                    </form>

                    <p data-notification-permission-status class="mt-3 text-xs font-bold leading-6 text-slate-500" role="status" aria-live="polite"></p>
                </div>
            </div>
        </section>

        @if ($unreadCount > 0)
            <div class="mb-5 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-brand-teal/25 bg-brand-teal/10 px-4 py-3">
                <p class="text-sm font-bold text-brand-ink">
                    عدد الإشعارات غير المقروءة: {{ $unreadCount }}
                </p>
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline bg-white">
                        <x-icon name="check" class="h-4 w-4" />
                        تعليم الكل كمقروء
                    </button>
                </form>
            </div>
        @endif

        @if ($notifications->isEmpty())
            <x-empty-state
                title="لا توجد إشعارات بعد"
                description="ستظهر هنا رسائل الترحيب وتحديثات حالة طلباتك فور حدوثها."
                action-label="تصفّح المنتجات"
                action-icon="box"
                :action-url="route('products.index')"
            />
        @else
            <ul class="space-y-3" aria-label="قائمة الإشعارات">
                @foreach ($notifications as $notification)
                    @php
                        $unread = $notification->read_at === null;
                        $category = data_get($notification->data, 'category', 'general');
                        $url = data_get($notification->data, 'url');
                        $icon = match ($category) {
                            'order' => 'orders',
                            'customer' => 'users',
                            'account' => 'user',
                            default => 'bell',
                        };
                    @endphp

                    <li>
                        <article @class([
                            'card p-4 sm:p-5',
                            'border-s-4 border-s-brand-teal bg-brand-teal/5' => $unread,
                        ])>
                            <div class="flex items-start gap-3">
                                <span @class([
                                    'grid h-10 w-10 shrink-0 place-items-center rounded-xl',
                                    'bg-brand-teal/15 text-brand-ink' => $unread,
                                    'bg-surface-muted text-slate-500' => ! $unread,
                                ]) aria-hidden="true">
                                    <x-icon :name="$icon" class="h-5 w-5" />
                                </span>

                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-start justify-between gap-2">
                                        <h2 class="text-sm font-extrabold text-brand-ink sm:text-base">
                                            {{ data_get($notification->data, 'title', 'إشعار جديد') }}
                                        </h2>
                                        @if ($unread)
                                            <span class="rounded-full bg-brand-teal/15 px-2.5 py-0.5 text-[0.65rem] font-extrabold text-brand-ink">غير مقروء</span>
                                        @endif
                                    </div>

                                    <p class="mt-1.5 text-sm leading-7 text-slate-600">
                                        {{ data_get($notification->data, 'body') }}
                                    </p>

                                    <div class="mt-2 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                                        <x-icon name="clock" class="h-3.5 w-3.5" />
                                        <time datetime="{{ $notification->created_at->toIso8601String() }}">{{ $notification->created_at->diffForHumans() }}</time>

                                        @if (data_get($notification->data, 'order_number'))
                                            <span aria-hidden="true">·</span>
                                            <span>رقم الطلب</span>
                                            <span dir="ltr" class="font-bold">{{ data_get($notification->data, 'order_number') }}</span>
                                        @endif
                                    </div>

                                    <div class="mt-3 flex flex-wrap gap-2">
                                        @if ($unread)
                                            <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-outline px-3 py-2 text-xs">
                                                    <x-icon name="eye" class="h-4 w-4" />
                                                    {{ is_string($url) && $url !== '' ? 'فتح وتعليم كمقروء' : 'تعليم كمقروء' }}
                                                </button>
                                            </form>
                                        @elseif (is_string($url) && $url !== '')
                                            <a href="{{ $url }}" class="btn btn-outline px-3 py-2 text-xs">
                                                <x-icon name="external" class="h-4 w-4" />
                                                فتح
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </article>
                    </li>
                @endforeach
            </ul>

            <div class="mt-8">{{ $notifications->links() }}</div>
        @endif
    </div>
@endsection
