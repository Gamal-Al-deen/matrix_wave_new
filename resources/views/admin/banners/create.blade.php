@extends('layouts.admin')

@section('title', 'إضافة بانر')

@section('content')
    <div class="space-y-6">
        <x-breadcrumbs :items="[
            ['label' => 'لوحة التحكم', 'url' => route('admin.dashboard')],
            ['label' => 'البانرات', 'url' => route('admin.banners.index')],
            ['label' => 'إضافة بانر'],
        ]" />

        <x-section-heading
            title="إضافة بانر"
            description="ارفع صورة البانر وأدخل بيانات النشر. يمكنك استبدال الصورة لاحقًا من صفحة التعديل."
        />

        <div class="card p-5 sm:p-6">
            <form method="POST" action="{{ route('admin.banners.store') }}" enctype="multipart/form-data" class="space-y-6">
                @csrf

                @include('admin.banners._form', ['banner' => null])

                <div class="flex flex-wrap items-center gap-3">
                    <button type="submit" class="btn btn-primary">
                        <x-icon name="save" class="h-4 w-4" />
                        حفظ البانر
                    </button>
                    <a href="{{ route('admin.banners.index') }}" class="btn btn-outline">
                        <x-icon name="close" class="h-4 w-4" />
                        إلغاء
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection
