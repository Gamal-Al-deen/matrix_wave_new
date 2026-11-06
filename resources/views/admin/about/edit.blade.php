@extends('layouts.admin')

@section('title', 'صفحة من نحن')

@section('content')
    <div class="space-y-6">
        <x-breadcrumbs :items="[
            ['label' => 'لوحة التحكم', 'url' => route('admin.dashboard')],
            ['label' => 'من نحن'],
        ]" />

        <x-section-heading
            title="صفحة من نحن"
            description="حرّر العنوان والمحتوى الذي يظهر للزوار في صفحة «من نحن» العامة."
        />

        <x-alert type="info">
            يُحفظ المحتوى كنص عادي ويُعرض بأمان؛ لا تُنفَّذ أكواد HTML أو JavaScript. استخدم فواصل الأسطر لتنظيم الفقرات.
        </x-alert>

        @if ($about->exists && $about->updated_at)
            <p class="text-xs text-slate-500">
                آخر تحديث:
                <span dir="ltr">{{ $about->updated_at->format('Y-m-d H:i') }}</span>
                @if ($about->updatedBy)
                    بواسطة <span class="font-bold text-brand-ink">{{ $about->updatedBy->name }}</span>
                @endif
            </p>
        @endif

        <div class="card p-5 sm:p-6">
            <form method="POST" action="{{ route('admin.about.update') }}" class="space-y-6">
                @csrf
                @method('PUT')

                <x-form-field label="عنوان الصفحة" name="title" :required="true">
                    <input
                        id="title"
                        name="title"
                        type="text"
                        value="{{ old('title', $about->title) }}"
                        required
                        maxlength="200"
                        autofocus
                        class="input"
                    >
                </x-form-field>

                <x-form-field
                    label="محتوى الصفحة"
                    name="body"
                    :required="true"
                    hint="نص عربي واضح. يمكنك استخدام الأسطر الفارغة للفصل بين الفقرات. الحد الأقصى 20,000 حرف."
                >
                    <textarea
                        id="body"
                        name="body"
                        rows="18"
                        required
                        maxlength="20000"
                        class="input font-normal leading-8"
                    >{{ old('body', $about->body) }}</textarea>
                </x-form-field>

                <div class="flex flex-wrap items-center gap-3">
                    <button type="submit" class="btn btn-primary">
                        <x-icon name="save" class="h-4 w-4" />
                        حفظ المحتوى
                    </button>
                    <a href="{{ route('about') }}" class="btn btn-outline" target="_blank" rel="noopener noreferrer">
                        <x-icon name="external" class="h-4 w-4" />
                        عرض الصفحة العامة
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection
