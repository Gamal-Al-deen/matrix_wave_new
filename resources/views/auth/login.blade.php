@extends('layouts.auth')

@section('title', 'تسجيل الدخول')
@section('subtitle', 'أدخل بريدك الإلكتروني وكلمة المرور للمتابعة.')

@section('content')
    <form method="POST" action="{{ route('login.store') }}" class="space-y-4">
        @csrf

        <x-form-field label="البريد الإلكتروني" name="email" required>
            <input
                id="email"
                name="email"
                type="email"
                value="{{ old('email') }}"
                required
                autofocus
                autocomplete="username"
                inputmode="email"
                dir="ltr"
                class="input text-start"
                @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
            >
        </x-form-field>

        <x-form-field label="كلمة المرور" name="password" required>
            <div class="relative">
                <input
                    id="password"
                    name="password"
                    type="password"
                    required
                    autocomplete="current-password"
                    class="input pe-12"
                    @error('password') aria-invalid="true" aria-describedby="password-error" @enderror
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

        <button type="submit" class="btn btn-primary w-full">
            <x-icon name="login" class="h-4 w-4" />
            دخول
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-600">
        ليس لديك حساب؟
        <a href="{{ route('register') }}" class="tap-link font-bold text-brand-ink underline decoration-brand-teal decoration-2 underline-offset-4 hover:text-brand-ink/80">
            أنشئ حسابًا جديدًا
        </a>
    </p>
@endsection
