@extends('layouts.admin')

@section('title', 'تعديل تصنيف')

@section('content')
    <div class="space-y-6">
        <x-breadcrumbs :items="[
            ['label' => 'لوحة التحكم', 'url' => route('admin.dashboard')],
            ['label' => 'التصنيفات', 'url' => route('admin.categories.index')],
            ['label' => $category->name],
        ]" />

        <x-section-heading title="تعديل التصنيف" :description="$category->name" />

        <div class="card p-5 sm:p-6">
            <form method="POST" action="{{ route('admin.categories.update', $category) }}" class="space-y-6">
                @csrf
                @method('PUT')

                @include('admin.categories._form', ['category' => $category])

                <div class="flex flex-wrap items-center gap-3">
                    <button type="submit" class="btn btn-primary">حفظ التعديلات</button>
                    <a href="{{ route('admin.categories.index') }}" class="btn btn-outline">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
@endsection
