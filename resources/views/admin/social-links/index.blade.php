@extends('layouts.admin')

@section('title', 'الروابط الاجتماعية')

@section('content')
    <div class="space-y-6">
        <x-breadcrumbs :items="[
            ['label' => 'لوحة التحكم', 'url' => route('admin.dashboard')],
            ['label' => 'الروابط الاجتماعية'],
        ]" />

        <x-section-heading
            title="الروابط الاجتماعية"
            description="أضف روابط المنصات الاجتماعية التي تظهر في تذييل الموقع. تظهر الروابط المفعّلة فقط، ولا تُنشأ أي روابط تلقائيًا."
            action-label="إضافة رابط"
            :action-url="route('admin.social-links.create')"
        />

        @if ($socialLinks->isEmpty())
            <x-empty-state
                title="لا توجد روابط اجتماعية بعد"
                description="أضف رابطًا يدويًا مع المنصة والرابط الكامل ليظهر في تذييل الموقع عند تفعيله."
                action-label="إضافة رابط"
                :action-url="route('admin.social-links.create')"
            />
        @else
            <div class="card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="responsive-table w-full min-w-[860px] text-sm">
                        <thead class="bg-surface-muted text-xs font-bold text-slate-600">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-start">المنصة</th>
                                <th scope="col" class="px-4 py-3 text-start">الرابط</th>
                                <th scope="col" class="px-4 py-3 text-start">الترتيب</th>
                                <th scope="col" class="px-4 py-3 text-start">الحالة</th>
                                <th scope="col" class="px-4 py-3 text-start">إجراءات</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-brand-ink/5">
                            @foreach ($socialLinks as $socialLink)
                                <tr>
                                    <td class="px-4 py-3" data-label="المنصة">
                                        <div class="flex min-w-0 items-center gap-3">
                                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-brand-ink text-white">
                                                <x-social-icon :platform="$socialLink->platform" class="h-5 w-5" />
                                            </span>
                                            <span class="font-bold text-brand-ink">{{ $socialLink->platform->label() }}</span>
                                        </div>
                                    </td>
                                    <td class="max-w-[360px] px-4 py-3" data-label="الرابط">
                                        <a href="{{ $socialLink->url }}" target="_blank" rel="noopener noreferrer" class="break-all text-slate-600 hover:text-brand-ink hover:underline" dir="ltr">
                                            {{ $socialLink->url }}
                                        </a>
                                    </td>
                                    <td class="px-4 py-3 text-slate-600" data-label="الترتيب">{{ $socialLink->sort_order }}</td>
                                    <td class="px-4 py-3" data-label="الحالة">
                                        @if ($socialLink->is_active)
                                            <span class="badge bg-success/10 text-success">مفعّل</span>
                                        @else
                                            <span class="badge bg-slate-100 text-slate-600">معطّل</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3" data-label="إجراءات">
                                        <div class="flex flex-wrap items-center gap-1">
                                            <a href="{{ route('admin.social-links.edit', $socialLink) }}" class="table-action gap-1.5 text-brand-ink hover:bg-brand-teal/10 hover:underline">
                                                <x-icon name="pencil" class="h-4 w-4" />
                                                تعديل
                                            </a>

                                            <form method="POST" action="{{ route('admin.social-links.status', $socialLink) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="table-action gap-1.5 text-info hover:bg-info/5 hover:underline">
                                                    <x-icon name="{{ $socialLink->is_active ? 'ban' : 'check' }}" class="h-4 w-4" />
                                                    {{ $socialLink->is_active ? 'تعطيل' : 'تفعيل' }}
                                                </button>
                                            </form>

                                            <x-confirm-form
                                                :action="route('admin.social-links.destroy', $socialLink)"
                                                title="حذف الرابط الاجتماعي"
                                                :message="'هل تريد حذف رابط '.$socialLink->platform->label().'؟'"
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

            <div>{{ $socialLinks->links() }}</div>
        @endif
    </div>
@endsection
