@extends('layouts.admin')

@section('title', 'تعديل محفظة')

@section('content')
    <div class="space-y-6">
        <x-breadcrumbs :items="[
            ['label' => 'لوحة التحكم', 'url' => route('admin.dashboard')],
            ['label' => 'المحافظ', 'url' => route('admin.wallets.index')],
            ['label' => $wallet->name],
        ]" />

        <x-section-heading title="تعديل المحفظة" :description="$wallet->name" />

        <x-alert type="info">
            يُستخدم اسم المحفظة الحالي في رسالة واتساب للطلبات السابقة لأن الطلب يحفظ معرف المحفظة فقط. لا يمكن حذف محفظة مرتبطة بطلبات.
        </x-alert>

        <div class="card p-5 sm:p-6">
            <form method="POST" action="{{ route('admin.wallets.update', $wallet) }}" class="space-y-6">
                @csrf
                @method('PUT')

                @include('admin.wallets._form', ['wallet' => $wallet])

                <div class="flex flex-wrap items-center gap-3">
                    <button type="submit" class="btn btn-primary">
                        <x-icon name="save" class="h-4 w-4" />
                        حفظ التعديلات
                    </button>
                    <a href="{{ route('admin.wallets.index') }}" class="btn btn-outline">
                        <x-icon name="close" class="h-4 w-4" />
                        إلغاء
                    </a>
                </div>
            </form>
        </div>

        @include('admin.wallets._icon', ['wallet' => $wallet])
    </div>
@endsection
