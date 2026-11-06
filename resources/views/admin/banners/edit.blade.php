@extends('layouts.admin')

@section('title', 'تعديل بانر')

@section('content')
    <div class="space-y-6">
        <x-breadcrumbs :items="[
            ['label' => 'لوحة التحكم', 'url' => route('admin.dashboard')],
            ['label' => 'البانرات', 'url' => route('admin.banners.index')],
            ['label' => $banner->title ?: $banner->alt_text],
        ]" />

        <x-section-heading title="تعديل البانر" :description="$banner->title ?: $banner->alt_text" />

        <div class="card p-5 sm:p-6">
            <form method="POST" action="{{ route('admin.banners.update', $banner) }}" class="space-y-6">
                @csrf
                @method('PUT')

                @include('admin.banners._form', ['banner' => $banner])

                <div class="flex flex-wrap items-center gap-3">
                    <button type="submit" class="btn btn-primary">
                        <x-icon name="save" class="h-4 w-4" />
                        حفظ التعديلات
                    </button>
                    <a href="{{ route('admin.banners.index') }}" class="btn btn-outline">
                        <x-icon name="close" class="h-4 w-4" />
                        إلغاء
                    </a>
                </div>
            </form>
        </div>

        @include('admin.banners._image', ['banner' => $banner])
    </div>
@endsection
