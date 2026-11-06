@extends('layouts.admin')

@section('title', 'تعديل منتج')

@section('content')
    <div class="space-y-6">
        <x-breadcrumbs :items="[
            ['label' => 'لوحة التحكم', 'url' => route('admin.dashboard')],
            ['label' => 'المنتجات', 'url' => route('admin.products.index')],
            ['label' => $product->name],
        ]" />

        <x-section-heading title="تعديل المنتج" :description="$product->name" />

        <div class="card p-5 sm:p-6">
            <form method="POST" action="{{ route('admin.products.update', $product) }}" class="space-y-6">
                @csrf
                @method('PUT')

                @include('admin.products._form', ['product' => $product])

                <div class="flex flex-wrap items-center gap-3">
                    <button type="submit" class="btn btn-primary">
                        <x-icon name="save" class="h-4 w-4" />
                        حفظ التعديلات
                    </button>
                    <a href="{{ route('admin.products.index') }}" class="btn btn-outline">
                        <x-icon name="close" class="h-4 w-4" />
                        إلغاء
                    </a>
                </div>
            </form>
        </div>

        @include('admin.products._images', ['product' => $product])
    </div>
@endsection
