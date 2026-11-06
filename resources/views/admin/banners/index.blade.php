@extends('layouts.admin')

@section('title', 'البانرات')

@section('content')
    <div class="space-y-6">
        <x-breadcrumbs :items="[
            ['label' => 'لوحة التحكم', 'url' => route('admin.dashboard')],
            ['label' => 'البانرات'],
        ]" />

        <x-section-heading
            title="البانرات"
            description="أضف صور البانرات، ورتّبها، وحدّد فترة نشرها. تظهر في الصفحة الرئيسية فقط البانرات المفعّلة التي يقع الوقت الحالي داخل فترة نشرها."
            action-label="إضافة بانر"
            action-icon="plus"
            :action-url="route('admin.banners.create')"
        />

        @if ($banners->isEmpty())
            <x-empty-state
                title="لا توجد بانرات بعد"
                description="أضف أول بانر ليظهر في الصفحة الرئيسية. عند عدم وجود بانر نشط يظهر التصميم التعريفي الافتراضي بدلًا منه."
                action-label="إضافة بانر"
                action-icon="plus"
                :action-url="route('admin.banners.create')"
            />
        @else
            <div class="card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="responsive-table w-full min-w-[980px] text-sm">
                        <thead class="bg-surface-muted text-xs font-bold text-slate-600">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-start">البانر</th>
                                <th scope="col" class="px-4 py-3 text-start">النشر</th>
                                <th scope="col" class="px-4 py-3 text-start">الترتيب</th>
                                <th scope="col" class="px-4 py-3 text-start">إجراءات</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-brand-ink/5">
                            @foreach ($banners as $banner)
                                @php
                                    if (! $banner->is_active) {
                                        $publication = ['label' => 'معطّل', 'classes' => 'bg-slate-100 text-slate-600'];
                                    } elseif ($banner->starts_at?->isFuture()) {
                                        $publication = ['label' => 'مجدول', 'classes' => 'bg-info/10 text-info'];
                                    } elseif ($banner->ends_at?->isPast()) {
                                        $publication = ['label' => 'منتهي', 'classes' => 'bg-warning/10 text-warning'];
                                    } else {
                                        $publication = ['label' => 'نشط الآن', 'classes' => 'bg-success/10 text-success'];
                                    }
                                @endphp

                                <tr>
                                    <td class="px-4 py-3" data-label="البانر">
                                        <div class="flex items-center gap-3">
                                            <span class="block h-14 w-24 shrink-0 overflow-hidden rounded-xl border border-brand-ink/10 bg-surface-muted">
                                                @if ($banner->image_public_url)
                                                    <img
                                                        src="{{ $banner->image_public_url }}"
                                                        alt=""
                                                        class="h-full w-full object-cover"
                                                        loading="lazy"
                                                    >
                                                @else
                                                    <span class="grid h-full w-full place-items-center text-slate-400">
                                                        <x-icon name="image" class="h-6 w-6" />
                                                    </span>
                                                @endif
                                            </span>

                                            <div class="min-w-0">
                                                <p class="font-bold text-brand-ink" dir="auto">{{ $banner->title ?: 'بدون عنوان' }}</p>
                                                <p class="mt-0.5 max-w-[260px] truncate text-xs text-slate-500" dir="auto">{{ $banner->alt_text }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3" data-label="النشر">
                                        <span class="badge {{ $publication['classes'] }}">{{ $publication['label'] }}</span>
                                        <div class="mt-1.5 space-y-0.5 text-xs leading-5 text-slate-500">
                                            @if ($banner->starts_at)
                                                <p>من: <span dir="ltr">{{ $banner->starts_at->format('Y-m-d H:i') }}</span></p>
                                            @endif
                                            @if ($banner->ends_at)
                                                <p>إلى: <span dir="ltr">{{ $banner->ends_at->format('Y-m-d H:i') }}</span></p>
                                            @endif
                                            @if (! $banner->starts_at && ! $banner->ends_at)
                                                <p>دون جدولة</p>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-slate-600" data-label="الترتيب">{{ $banner->sort_order }}</td>
                                    <td class="px-4 py-3" data-label="إجراءات">
                                        <div class="flex flex-wrap items-center gap-1">
                                            <a href="{{ route('admin.banners.edit', $banner) }}" class="table-action gap-1.5 text-brand-ink hover:underline">
                                                <x-icon name="pencil" class="h-4 w-4" />
                                                تعديل
                                            </a>

                                            <form method="POST" action="{{ route('admin.banners.status', $banner) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="table-action gap-1.5 text-info hover:bg-info/5 hover:underline">
                                                    <x-icon :name="$banner->is_active ? 'ban' : 'check'" class="h-4 w-4" />
                                                    {{ $banner->is_active ? 'تعطيل' : 'تفعيل' }}
                                                </button>
                                            </form>

                                            <x-confirm-form
                                                :action="route('admin.banners.destroy', $banner)"
                                                title="حذف البانر"
                                                :message="'هل تريد حذف البانر «'.($banner->title ?: $banner->alt_text).'»؟ سيتم حذف ملف الصورة أيضًا.'"
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

            <div>{{ $banners->links() }}</div>
        @endif
    </div>
@endsection
