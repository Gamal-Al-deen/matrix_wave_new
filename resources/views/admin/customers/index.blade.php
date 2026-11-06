@extends('layouts.admin')

@section('title', 'العملاء')

@section('content')
    @php
        $hasSearch = $search !== '';
    @endphp

    <div class="space-y-6">
        <x-breadcrumbs :items="[
            ['label' => 'لوحة التحكم', 'url' => route('admin.dashboard')],
            ['label' => 'العملاء'],
        ]" />

        <x-section-heading
            title="العملاء"
            description="راجع حسابات العملاء المسجّلين وعدد طلبات كل عميل، وافتح الحساب لمراجعة بياناته وسجل طلباته."
        />

        <form method="GET" action="{{ route('admin.customers.index') }}" class="card p-4 sm:p-5" role="search" aria-label="البحث في العملاء">
            <div class="grid gap-4 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-end">
                <x-form-field label="بحث" name="q" hint="اسم العميل أو البريد الإلكتروني أو رقم الهاتف.">
                    <input
                        id="q"
                        name="q"
                        type="search"
                        value="{{ $search }}"
                        maxlength="100"
                        class="input"
                        placeholder="ابحث باسم العميل أو بريده أو هاتفه"
                    >
                </x-form-field>

                <div class="flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-primary">
                        <x-icon name="search" class="h-4 w-4" />
                        بحث
                    </button>
                    <a href="{{ route('admin.customers.index') }}" class="btn btn-outline">مسح</a>
                </div>
            </div>
        </form>

        @if ($customers->isEmpty())
            <x-empty-state
                :title="$hasSearch ? 'لا يوجد عملاء مطابقون' : 'لا يوجد عملاء بعد'"
                :description="$hasSearch
                    ? 'جرّب تعديل كلمة البحث أو امسحها لعرض كل العملاء المسجّلين.'
                    : 'ستظهر هنا حسابات العملاء الجديدة بمجرد تسجيلهم في المتجر.'"
                :action-label="$hasSearch ? 'مسح البحث' : null"
                :action-url="$hasSearch ? route('admin.customers.index') : null"
            />
        @else
            <div class="card overflow-hidden">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-brand-ink/10 px-4 py-3">
                    <p class="text-xs font-bold text-slate-600">{{ $customers->total() }} عميل</p>
                    <p class="text-xs text-slate-500">الأحدث تسجيلًا أولًا</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="responsive-table w-full min-w-[820px] text-sm">
                        <thead class="bg-surface-muted text-xs font-bold text-slate-600">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-start">العميل</th>
                                <th scope="col" class="px-4 py-3 text-start">الهاتف</th>
                                <th scope="col" class="px-4 py-3 text-start">تاريخ التسجيل</th>
                                <th scope="col" class="px-4 py-3 text-start">الطلبات</th>
                                <th scope="col" class="px-4 py-3 text-start">إجراء</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-brand-ink/5">
                            @foreach ($customers as $customer)
                                <tr>
                                    <td class="px-4 py-3" data-label="العميل">
                                        <div class="flex items-center gap-3">
                                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-brand-teal/15 text-sm font-extrabold text-brand-ink" aria-hidden="true">
                                                {{ mb_substr($customer->name, 0, 1) }}
                                            </span>

                                            <div class="min-w-0">
                                                <p class="truncate font-bold text-brand-ink">{{ $customer->name }}</p>
                                                <p class="mt-0.5 truncate text-xs text-slate-500" dir="ltr">{{ $customer->email }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-slate-600" data-label="الهاتف">
                                        @if (filled($customer->phone))
                                            <span dir="ltr">{{ $customer->phone }}</span>
                                        @else
                                            <span class="text-slate-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-slate-600" data-label="تاريخ التسجيل">
                                        <time datetime="{{ $customer->created_at->toIso8601String() }}" dir="ltr">{{ $customer->created_at->format('Y-m-d') }}</time>
                                    </td>
                                    <td class="px-4 py-3" data-label="الطلبات">
                                        <span class="badge bg-surface-muted text-brand-ink">{{ $customer->orders_count }}</span>
                                    </td>
                                    <td class="px-4 py-3" data-label="إجراء">
                                        <a href="{{ route('admin.customers.show', $customer) }}" class="table-action gap-1.5 text-brand-ink hover:underline">
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
                    {{ $customers->links() }}
                </div>
            </div>
        @endif
    </div>
@endsection
