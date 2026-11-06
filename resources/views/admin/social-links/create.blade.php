@extends('layouts.admin')

@section('title', 'إضافة رابط اجتماعي')

@section('content')
    <div class="space-y-6">
        <x-breadcrumbs :items="[
            ['label' => 'لوحة التحكم', 'url' => route('admin.dashboard')],
            ['label' => 'الروابط الاجتماعية', 'url' => route('admin.social-links.index')],
            ['label' => 'إضافة رابط'],
        ]" />

        <x-section-heading
            title="إضافة رابط اجتماعي"
            description="اختر المنصة وأدخل الرابط الكامل يدويًا. لا تُنشأ روابط المنصات تلقائيًا."
        />

        <div class="card p-5 sm:p-6">
            <form method="POST" action="{{ route('admin.social-links.store') }}" class="space-y-6">
                @csrf

                @include('admin.social-links._form', ['socialLink' => null, 'platforms' => $platforms])

                <div class="flex flex-wrap items-center gap-3">
                    <button type="submit" class="btn btn-primary">
                        <x-icon name="plus" class="h-4 w-4" />
                        إضافة الرابط
                    </button>
                    <a href="{{ route('admin.social-links.index') }}" class="btn btn-outline">
                        <x-icon name="close" class="h-4 w-4" />
                        إلغاء
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection
