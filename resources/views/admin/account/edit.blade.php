@extends('layouts.admin')

@section('title', 'حسابي')

@section('content')
    <div class="space-y-6">
        <x-breadcrumbs :items="[
            ['label' => 'لوحة التحكم', 'url' => route('admin.dashboard')],
            ['label' => 'حسابي'],
        ]" />

        <x-section-heading
            title="حسابي"
            description="حدّث بريد الدخول وكلمة المرور الخاصة بحساب المدير. لا تُعرض كلمة المرور المحفوظة في هذه الصفحة."
        />

        <div class="card p-5 sm:p-6">
            <form method="POST" action="{{ route('admin.account.update') }}" class="space-y-6">
                @csrf
                @method('PUT')

                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <x-form-field label="البريد الإلكتروني" name="email" :required="true" hint="يُستخدم لتسجيل الدخول إلى لوحة الإدارة.">
                            <input
                                id="email"
                                name="email"
                                type="email"
                                value="{{ old('email', $user->email) }}"
                                required
                                maxlength="255"
                                dir="ltr"
                                inputmode="email"
                                autocomplete="username"
                                @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
                                class="input text-start @error('email') border-danger @enderror"
                            >
                        </x-form-field>
                    </div>

                    <div class="sm:col-span-2">
                        <x-form-field label="كلمة المرور الحالية" name="current_password" :required="true" hint="مطلوبة لتأكيد أي تغيير على بيانات الحساب.">
                            <div class="relative">
                                <input
                                    id="current_password"
                                    name="current_password"
                                    type="password"
                                    required
                                    autocomplete="current-password"
                                    @error('current_password') aria-invalid="true" aria-describedby="current_password-error" @enderror
                                    class="input pe-12 @error('current_password') border-danger @enderror"
                                >
                                <button
                                    type="button"
                                    class="absolute inset-y-0 end-0 grid w-11 place-items-center rounded-e-xl text-slate-500 hover:text-brand-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-teal"
                                    data-password-toggle="current_password"
                                    aria-pressed="false"
                                    aria-label="إظهار كلمة المرور"
                                >
                                    <x-icon name="eye" class="h-5 w-5" />
                                </button>
                            </div>
                        </x-form-field>
                    </div>

                    <x-form-field label="كلمة المرور الجديدة (اختياري)" name="password" hint="اتركها فارغة للإبقاء على كلمة المرور الحالية. 8 أحرف على الأقل.">
                        <div class="relative">
                            <input
                                id="password"
                                name="password"
                                type="password"
                                autocomplete="new-password"
                                @error('password') aria-invalid="true" aria-describedby="password-error" @enderror
                                class="input pe-12 @error('password') border-danger @enderror"
                            >
                            <button
                                type="button"
                                class="absolute inset-y-0 end-0 grid w-11 place-items-center rounded-e-xl text-slate-500 hover:text-brand-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-teal"
                                data-password-toggle="password"
                                aria-pressed="false"
                                aria-label="إظهار كلمة المرور"
                            >
                                <x-icon name="eye" class="h-5 w-5" />
                            </button>
                        </div>
                    </x-form-field>

                    <x-form-field label="تأكيد كلمة المرور الجديدة" name="password_confirmation" hint="أعد كتابة كلمة المرور الجديدة عند تغييرها.">
                        <div class="relative">
                            <input
                                id="password_confirmation"
                                name="password_confirmation"
                                type="password"
                                autocomplete="new-password"
                                class="input pe-12"
                            >
                            <button
                                type="button"
                                class="absolute inset-y-0 end-0 grid w-11 place-items-center rounded-e-xl text-slate-500 hover:text-brand-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-teal"
                                data-password-toggle="password_confirmation"
                                aria-pressed="false"
                                aria-label="إظهار كلمة المرور"
                            >
                                <x-icon name="eye" class="h-5 w-5" />
                            </button>
                        </div>
                    </x-form-field>
                </div>

                <x-alert type="info">
                    كلمة المرور الحالية مطلوبة لتأكيد التغييرات، ولا تُعرض كلمة المرور المحفوظة في أي مكان في هذه الصفحة.
                </x-alert>

                <div class="flex flex-wrap items-center gap-3">
                    <button type="submit" class="btn btn-primary">
                        <x-icon name="save" class="h-4 w-4" />
                        حفظ التغييرات
                    </button>
                    <a href="{{ route('admin.dashboard') }}" class="btn btn-outline">
                        <x-icon name="close" class="h-4 w-4" />
                        إلغاء
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection
