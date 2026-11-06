@extends('layouts.auth')

@section('title', 'إنشاء حساب')
@section('subtitle', 'أنشئ حسابك لمتابعة طلباتك وإتمام الشراء بسهولة.')

@section('content')
    <form method="POST" action="{{ route('register.store') }}" class="space-y-4">
        @csrf

        <x-form-field label="الاسم الكامل" name="name" required>
            <input
                id="name"
                name="name"
                type="text"
                value="{{ old('name') }}"
                required
                autofocus
                autocomplete="name"
                class="input"
                @error('name') aria-invalid="true" aria-describedby="name-error" @enderror
            >
        </x-form-field>

        <x-form-field label="البريد الإلكتروني" name="email" required>
            <input
                id="email"
                name="email"
                type="email"
                value="{{ old('email') }}"
                required
                autocomplete="username"
                inputmode="email"
                dir="ltr"
                class="input text-start"
                @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
            >
        </x-form-field>

        <x-form-field label="رقم الهاتف" name="phone" hint="اختياري. رقم يمني محلي من 9 أرقام يبدأ بـ 70 أو 71 أو 73 أو 77 أو 78، دون +967.">
            <input
                id="phone"
                name="phone"
                type="tel"
                value="{{ old('phone') }}"
                autocomplete="tel"
                inputmode="numeric"
                minlength="9"
                maxlength="9"
                pattern="(?:7[01378]|٧[٠١٣٧٨])[0-9٠-٩]{7}"
                dir="ltr"
                class="input text-start"
                @error('phone') aria-invalid="true" @enderror
                aria-describedby="phone-hint{{ $errors->has('phone') ? ' phone-error' : '' }}"
            >
        </x-form-field>

        <x-form-field label="كلمة المرور" name="password" hint="8 أحرف على الأقل." required>
            <div class="relative">
                <input
                    id="password"
                    name="password"
                    type="password"
                    required
                    autocomplete="new-password"
                    class="input pe-12"
                    @error('password') aria-invalid="true" @enderror
                    aria-describedby="password-hint{{ $errors->has('password') ? ' password-error' : '' }}"
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

        <x-form-field label="تأكيد كلمة المرور" name="password_confirmation" required>
            <div class="relative">
                <input
                    id="password_confirmation"
                    name="password_confirmation"
                    type="password"
                    required
                    autocomplete="new-password"
                    class="input pe-12"
                    @error('password_confirmation') aria-invalid="true" aria-describedby="password_confirmation-error" @enderror
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

        <button type="submit" class="btn btn-primary w-full">
            <x-icon name="user" class="h-4 w-4" />
            إنشاء الحساب
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-600">
        لديك حساب بالفعل؟
        <a href="{{ route('login') }}" class="tap-link font-bold text-brand-ink underline decoration-brand-teal decoration-2 underline-offset-4 hover:text-brand-ink/80">
            تسجيل الدخول
        </a>
    </p>
@endsection
