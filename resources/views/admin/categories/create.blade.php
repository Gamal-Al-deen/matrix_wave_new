@extends('layouts.admin')

@section('title', 'إضافة تصنيف')

@section('content')
    <div class="space-y-6">
        <x-breadcrumbs :items="[
            ['label' => 'لوحة التحكم', 'url' => route('admin.dashboard')],
            ['label' => 'التصنيفات', 'url' => route('admin.categories.index')],
            ['label' => 'إضافة تصنيف'],
        ]" />

        <x-section-heading title="إضافة تصنيف" description="أدخل بيانات التصنيف ثم احفظه." />

        <div class="card p-5 sm:p-6">
            <form method="POST" action="{{ route('admin.categories.store') }}" class="space-y-6">
                @csrf

                @include('admin.categories._form', ['category' => null])

                <div class="flex flex-wrap items-center gap-3">
                    <button type="submit" class="btn btn-primary">
                        <x-icon name="save" class="h-4 w-4" />
                        حفظ التصنيف
                    </button>
                    <a href="{{ route('admin.categories.index') }}" class="btn btn-outline">
                        <x-icon name="close" class="h-4 w-4" />
                        إلغاء
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection
