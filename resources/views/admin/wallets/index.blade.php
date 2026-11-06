@extends('layouts.admin')

@section('title', 'المحافظ')

@section('content')
    <div class="space-y-6">
        <x-breadcrumbs :items="[
            ['label' => 'لوحة التحكم', 'url' => route('admin.dashboard')],
            ['label' => 'المحافظ'],
        ]" />

        <x-section-heading
            title="المحافظ"
            description="أضف محافظ الدفع ورتّبها وفعّلها. اختيار المحفظة يحدد وسيلة التحويل المفضلة فقط، ولا يؤكد الدفع. لا يمكن حذف محفظة مرتبطة بطلبات."
            action-label="إضافة محفظة"
            action-icon="plus"
            :action-url="route('admin.wallets.create')"
        />

        @if ($wallets->isEmpty())
            <x-empty-state
                title="لا توجد محافظ بعد"
                description="ابدأ بإضافة محفظة ليتمكن العملاء من اختيارها اختياريًا عند إتمام الطلب."
                action-label="إضافة محفظة"
                action-icon="plus"
                :action-url="route('admin.wallets.create')"
            />
        @else
            <div class="card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="responsive-table w-full min-w-[860px] text-sm">
                        <thead class="bg-surface-muted text-xs font-bold text-slate-600">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-start">المحفظة</th>
                                <th scope="col" class="px-4 py-3 text-start">معرف التحويل</th>
                                <th scope="col" class="px-4 py-3 text-start">الترتيب</th>
                                <th scope="col" class="px-4 py-3 text-start">الحالة</th>
                                <th scope="col" class="px-4 py-3 text-start">الطلبات</th>
                                <th scope="col" class="px-4 py-3 text-start">إجراءات</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-brand-ink/5">
                            @foreach ($wallets as $wallet)
                                <tr>
                                    <td class="px-4 py-3" data-label="المحفظة">
                                        <div class="flex items-center gap-3">
                                            <span class="grid h-11 w-11 shrink-0 place-items-center overflow-hidden rounded-xl border border-brand-ink/10 bg-surface-muted">
                                                @if ($wallet->icon_public_url)
                                                    <img
                                                        src="{{ $wallet->icon_public_url }}"
                                                        alt=""
                                                        class="h-full w-full object-contain p-1"
                                                        loading="lazy"
                                                    >
                                                @else
                                                    <x-icon name="wallet" class="h-5 w-5 text-slate-400" />
                                                @endif
                                            </span>

                                            <div class="min-w-0">
                                                <p class="font-bold text-brand-ink">{{ $wallet->name }}</p>
                                                <p class="mt-0.5 text-xs text-slate-500">
                                                    {{ $wallet->icon_public_url ? 'أيقونة مرفوعة' : 'بدون أيقونة' }}
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="max-w-[220px] px-4 py-3" data-label="معرف التحويل">
                                        <p class="break-all text-slate-600" dir="auto">{{ $wallet->account_identifier }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-slate-600" data-label="الترتيب">{{ $wallet->sort_order }}</td>
                                    <td class="px-4 py-3" data-label="الحالة">
                                        @if ($wallet->is_active)
                                            <span class="badge bg-success/10 text-success">مفعّلة</span>
                                        @else
                                            <span class="badge bg-slate-100 text-slate-600">معطّلة</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-slate-600" data-label="الطلبات">{{ $wallet->orders_count }}</td>
                                    <td class="px-4 py-3" data-label="إجراءات">
                                        <div class="flex flex-wrap items-center gap-1">
                                            <a href="{{ route('admin.wallets.edit', $wallet) }}" class="table-action gap-1.5 text-brand-ink hover:underline">
                                                <x-icon name="pencil" class="h-4 w-4" />
                                                تعديل
                                            </a>

                                            <form method="POST" action="{{ route('admin.wallets.status', $wallet) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="table-action gap-1.5 text-info hover:bg-info/5 hover:underline">
                                                    <x-icon :name="$wallet->is_active ? 'ban' : 'check'" class="h-4 w-4" />
                                                    {{ $wallet->is_active ? 'تعطيل' : 'تفعيل' }}
                                                </button>
                                            </form>

                                            @if ($wallet->orders_count === 0)
                                                <x-confirm-form
                                                    :action="route('admin.wallets.destroy', $wallet)"
                                                    title="حذف المحفظة"
                                                    :message="'هل تريد حذف المحفظة «'.$wallet->name.'»؟ سيتم حذف أيقونتها أيضًا إن وجدت.'"
                                                    label="حذف"
                                                />
                                            @else
                                                <span class="inline-flex min-h-9 cursor-not-allowed items-center gap-1.5 rounded-lg px-2 text-xs font-bold text-slate-400" title="لا يمكن حذف محفظة مرتبطة بطلبات">
                                                    <x-icon name="trash" class="h-4 w-4" />
                                                    حذف
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div>{{ $wallets->links() }}</div>
        @endif
    </div>
@endsection
