@extends('layouts.admin')

@section('title', 'التصنيفات')

@section('content')
    <div class="space-y-6">
        <x-breadcrumbs :items="[
            ['label' => 'لوحة التحكم', 'url' => route('admin.dashboard')],
            ['label' => 'التصنيفات'],
        ]" />

        <x-section-heading
            title="التصنيفات"
            description="أنشئ التصنيفات وفعّلها أو عطّلها. لا يمكن حذف تصنيف مرتبط بمنتجات."
            action-label="إضافة تصنيف"
            action-icon="plus"
            :action-url="route('admin.categories.create')"
        />

        @if ($categories->isEmpty())
            <x-empty-state
                title="لا توجد تصنيفات بعد"
                description="ابدأ بإنشاء أول تصنيف لتنظيم المنتجات."
                action-label="إضافة تصنيف"
                action-icon="plus"
                :action-url="route('admin.categories.create')"
            />
        @else
            <div class="card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="responsive-table w-full min-w-[640px] text-sm">
                        <thead class="bg-surface-muted text-xs font-bold text-slate-600">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-start">التصنيف</th>
                                <th scope="col" class="px-4 py-3 text-start">عدد المنتجات</th>
                                <th scope="col" class="px-4 py-3 text-start">الحالة</th>
                                <th scope="col" class="px-4 py-3 text-start">إجراءات</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-brand-ink/5">
                            @foreach ($categories as $category)
                                <tr>
                                    <td class="px-4 py-3" data-label="التصنيف">
                                        <p class="font-bold text-brand-ink">{{ $category->name }}</p>
                                        <p class="mt-0.5 text-xs text-slate-500" dir="ltr">{{ $category->slug }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-slate-600" data-label="عدد المنتجات">{{ $category->products_count }}</td>
                                    <td class="px-4 py-3" data-label="الحالة">
                                        @if ($category->is_active)
                                            <span class="badge bg-success/10 text-success">مفعّل</span>
                                        @else
                                            <span class="badge bg-slate-100 text-slate-600">معطّل</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3" data-label="إجراءات">
                                        <div class="flex flex-wrap items-center gap-1">
                                            <a href="{{ route('admin.categories.edit', $category) }}" class="table-action gap-1.5 text-brand-ink hover:underline">
                                                <x-icon name="pencil" class="h-4 w-4" />
                                                تعديل
                                            </a>

                                            <form method="POST" action="{{ route('admin.categories.status', $category) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="table-action gap-1.5 text-info hover:bg-info/5 hover:underline">
                                                    <x-icon :name="$category->is_active ? 'ban' : 'check'" class="h-4 w-4" />
                                                    {{ $category->is_active ? 'تعطيل' : 'تفعيل' }}
                                                </button>
                                            </form>

                                            @if ($category->products_count === 0)
                                                <x-confirm-form
                                                    :action="route('admin.categories.destroy', $category)"
                                                    title="حذف التصنيف"
                                                    :message="'هل تريد حذف التصنيف «'.$category->name.'»؟'"
                                                    label="حذف"
                                                />
                                            @else
                                                <span class="inline-flex min-h-9 cursor-not-allowed items-center gap-1.5 rounded-lg px-2 text-xs font-bold text-slate-400" title="لا يمكن حذف تصنيف مرتبط بمنتجات">
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

            <div>{{ $categories->links() }}</div>
        @endif
    </div>
@endsection
