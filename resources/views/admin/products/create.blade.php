@extends('layouts.admin')

@section('title', 'إضافة منتج')

@section('content')
    <div class="space-y-6">
        <x-breadcrumbs :items="[
            ['label' => 'لوحة التحكم', 'url' => route('admin.dashboard')],
            ['label' => 'المنتجات', 'url' => route('admin.products.index')],
            ['label' => 'إضافة منتج'],
        ]" />

        <x-section-heading title="إضافة منتج" description="بعد الحفظ ستنتقل إلى صفحة التعديل لإضافة الصور." />

        @if ($categories->isEmpty())
            <x-alert type="warning">
                يجب إنشاء تصنيف واحد على الأقل قبل إضافة منتج.
                <a href="{{ route('admin.categories.create') }}" class="font-bold underline">إضافة تصنيف</a>
            </x-alert>
        @else
            <div class="card p-5 sm:p-6">
                <form method="POST" action="{{ route('admin.products.store') }}" class="space-y-6">
                    @csrf

                    @include('admin.products._form', ['product' => null])

                    <div class="flex flex-wrap items-center gap-3">
                        <button type="submit" class="btn btn-primary">حفظ المنتج</button>
                        <a href="{{ route('admin.products.index') }}" class="btn btn-outline">إلغاء</a>
                    </div>
                </form>
            </div>
        @endif
    </div>
@endsection
