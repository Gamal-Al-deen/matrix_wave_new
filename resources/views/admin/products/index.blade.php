@extends('layouts.admin')

@section('title', 'المنتجات')

@section('content')
    @php
        $currency = \App\Support\Currency::code();
    @endphp

    <div class="space-y-6">
        <x-breadcrumbs :items="[
            ['label' => 'لوحة التحكم', 'url' => route('admin.dashboard')],
            ['label' => 'المنتجات'],
        ]" />

        <x-section-heading
            title="المنتجات"
            description="أضف المنتجات وحدّث أسعارها وحالة توفرها وصورها."
            action-label="إضافة منتج"
            action-icon="plus"
            :action-url="route('admin.products.create')"
        />

        <div class="flex flex-wrap items-center gap-2">
            <span class="badge min-h-11 gap-1.5 border border-brand-ink bg-brand-ink px-3 text-white">
                <x-icon name="check" class="h-4 w-4" />
                المنتجات الحالية
            </span>
        </div>

        @if ($products->isEmpty())
            <x-empty-state
                title="لا توجد منتجات بعد"
                description="ابدأ بإضافة منتج وربطه بتصنيف، ثم أضف صوره."
                action-label="إضافة منتج"
                action-icon="plus"
                :action-url="route('admin.products.create')"
            />
        @else
            <div class="card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="responsive-table w-full min-w-[880px] text-sm">
                        <thead class="bg-surface-muted text-xs font-bold text-slate-600">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-start">المنتج</th>
                                <th scope="col" class="px-4 py-3 text-start">التصنيف</th>
                                <th scope="col" class="px-4 py-3 text-start">السعر</th>
                                <th scope="col" class="px-4 py-3 text-start">الحالة</th>
                                <th scope="col" class="px-4 py-3 text-start">الصور</th>
                                <th scope="col" class="px-4 py-3 text-start">إجراءات</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-brand-ink/5">
                            @foreach ($products as $product)
                                @php
                                    $image = $product->images->first();
                                @endphp
                                <tr>
                                    <td class="px-4 py-3" data-label="المنتج">
                                        <div class="flex items-center gap-3">
                                            <div class="h-12 w-12 shrink-0 overflow-hidden rounded-lg bg-surface-muted">
                                                @if ($image?->public_url)
                                                    <img
                                                        src="{{ $image->public_url }}"
                                                        alt="{{ $image->alt_text ?: $product->name }}"
                                                        class="h-full w-full object-cover"
                                                        loading="lazy"
                                                    >
                                                @else
                                                    <div class="grid h-full place-items-center text-slate-400">
                                                        <x-icon name="image" class="h-5 w-5" />
                                                    </div>
                                                @endif
                                            </div>

                                            <div class="min-w-0">
                                                <p class="truncate font-bold text-brand-ink">{{ $product->name }}</p>
                                                <p class="mt-0.5 truncate text-xs text-slate-500" dir="ltr">{{ $product->slug }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-slate-600" data-label="التصنيف">{{ $product->category?->name ?? '—' }}</td>
                                    <td class="px-4 py-3 font-bold text-brand-ink" dir="ltr" data-label="السعر">
                                        {{ number_format((float) $product->price, 2) }} {{ $currency }}
                                    </td>
                                    <td class="px-4 py-3" data-label="الحالة">
                                        <span class="badge {{ $product->status->badgeClasses() }}">{{ $product->status->label() }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-slate-600" data-label="الصور">{{ $product->images_count }}</td>
                                    <td class="px-4 py-3" data-label="إجراءات">
                                        <div class="flex flex-wrap items-center gap-1">
                                            <a href="{{ route('admin.products.edit', $product) }}" class="table-action gap-1.5 text-brand-ink hover:underline">
                                                <x-icon name="pencil" class="h-4 w-4" />
                                                تعديل
                                            </a>

                                            <x-confirm-form
                                                :action="route('admin.products.destroy', $product)"
                                                title="حذف المنتج"
                                                message="سيُحذف المنتج نهائيًا مع الحفاظ على بيانات الطلبات السابقة. لا يمكن التراجع عن هذا الإجراء."
                                                label="حذف"
                                            />
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div>{{ $products->links() }}</div>
        @endif
    </div>
@endsection
