@extends('layouts.admin')

@section('title', 'إضافة منتج')

@section('content')
    <div class="space-y-6">
        <x-breadcrumbs :items="[
            ['label' => 'لوحة التحكم', 'url' => route('admin.dashboard')],
            ['label' => 'المنتجات', 'url' => route('admin.products.index')],
            ['label' => 'إضافة منتج'],
        ]" />

        <x-section-heading title="إضافة منتج" description="أدخل بيانات المنتج، ارفع صورته، ثم انشره ليظهر في المتجر." />

        @if ($categories->isEmpty())
            <x-alert type="warning">
                يجب إنشاء تصنيف واحد على الأقل قبل إضافة منتج.
                <a href="{{ route('admin.categories.create') }}" class="font-bold underline">إضافة تصنيف</a>
            </x-alert>
        @else
            <div class="card p-5 sm:p-6">
                <form
                    method="POST"
                    action="{{ route('admin.products.store') }}"
                    class="space-y-6"
                    data-product-create
                    data-upload-url="{{ route('admin.products.images.draft') }}"
                    data-delete-url-template="{{ route('admin.products.images.draft.destroy', ['key' => '__KEY__']) }}"
                    data-max-images="{{ (int) config('media.max_product_images', 10) }}"
                >
                    @csrf

                    @include('admin.products._form', ['product' => null])

                    @include('admin.products._draft-image-uploader', ['draftImages' => $draftImages])

                    <div class="flex flex-wrap items-center gap-3">
                        <button
                            type="submit"
                            class="btn btn-primary gap-1.5 opacity-60 cursor-not-allowed"
                            data-product-publish
                            disabled
                            aria-disabled="true"
                        >
                            <x-icon name="upload" class="h-4 w-4" />
                            نشر المنتج
                        </button>
                        <a href="{{ route('admin.products.index') }}" class="btn btn-outline">إلغاء</a>
                    </div>
                </form>
            </div>
        @endif
    </div>
@endsection
