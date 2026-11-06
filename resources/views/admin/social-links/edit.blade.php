@extends('layouts.admin')

@section('title', 'تعديل رابط اجتماعي')

@section('content')
    <div class="space-y-6">
        <x-breadcrumbs :items="[
            ['label' => 'لوحة التحكم', 'url' => route('admin.dashboard')],
            ['label' => 'الروابط الاجتماعية', 'url' => route('admin.social-links.index')],
            ['label' => $socialLink->platform->label()],
        ]" />

        <x-section-heading
            title="تعديل الرابط الاجتماعي"
            description="حدّث المنصة أو الرابط أو الترتيب أو حالة الظهور."
        />

        <div class="card p-5 sm:p-6">
            <form method="POST" action="{{ route('admin.social-links.update', $socialLink) }}" class="space-y-6">
                @csrf
                @method('PUT')

                @include('admin.social-links._form', ['socialLink' => $socialLink, 'platforms' => $platforms])

                <div class="flex flex-wrap items-center gap-3">
                    <button type="submit" class="btn btn-primary">
                        <x-icon name="save" class="h-4 w-4" />
                        حفظ التعديلات
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
