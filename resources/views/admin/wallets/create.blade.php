@extends('layouts.admin')

@section('title', 'إضافة محفظة')

@section('content')
    <div class="space-y-6">
        <x-breadcrumbs :items="[
            ['label' => 'لوحة التحكم', 'url' => route('admin.dashboard')],
            ['label' => 'المحافظ', 'url' => route('admin.wallets.index')],
            ['label' => 'إضافة محفظة'],
        ]" />

        <x-section-heading title="إضافة محفظة" description="أدخل بيانات المحفظة ثم احفظها. يمكنك رفع الأيقونة بعد الحفظ." />

        <div class="card p-5 sm:p-6">
            <form method="POST" action="{{ route('admin.wallets.store') }}" class="space-y-6">
                @csrf

                @include('admin.wallets._form', ['wallet' => null])

                <div class="flex flex-wrap items-center gap-3">
                    <button type="submit" class="btn btn-primary">
                        <x-icon name="save" class="h-4 w-4" />
                        حفظ المحفظة
                    </button>
                    <a href="{{ route('admin.wallets.index') }}" class="btn btn-outline">
                        <x-icon name="close" class="h-4 w-4" />
                        إلغاء
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection
