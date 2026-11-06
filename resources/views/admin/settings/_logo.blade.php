@props([
    'settings',
])

@php
    $hasCustomLogo = $settings->hasCustomLogo();
    $logoUrl = $settings->logoUrl();
@endphp

<section class="card p-5 sm:p-6" aria-labelledby="site-logo-title">
    <h2 id="site-logo-title" class="text-lg font-extrabold text-brand-ink">شعار الموقع</h2>
    <p class="mt-1 text-sm leading-7 text-slate-600">
        يظهر الشعار في رأس الصفحة وتذييلها وصفحات الدخول ولوحة الإدارة والفواتير. عند عدم رفع شعار مخصص يُستخدم شعار Matrix Wave الرسمي المرفق.
    </p>

    <div class="mt-5 flex flex-col gap-5 sm:flex-row sm:items-start">
        <div class="grid min-h-28 w-full max-w-[260px] shrink-0 place-items-center overflow-hidden rounded-2xl border border-brand-ink/10 bg-brand-ink p-4">
            <img
                src="{{ $logoUrl }}"
                alt="شعار {{ $settings->displayName() }}"
                class="block h-auto max-h-28 w-full object-contain"
            >
        </div>

        <div class="min-w-0 flex-1 space-y-4">
            <p class="text-xs font-bold text-slate-500">
                {{ $hasCustomLogo ? 'الشعار الحالي مرفوع من لوحة الإدارة.' : 'الشعار الرسمي الافتراضي مستخدم حاليًا.' }}
            </p>

            <form
                method="POST"
                action="{{ route('admin.settings.logo.store') }}"
                enctype="multipart/form-data"
                class="rounded-2xl border border-dashed border-brand-ink/20 bg-surface-muted p-4"
            >
                @csrf

                <label for="site-logo" class="block text-sm font-bold text-ink">
                    {{ $hasCustomLogo ? 'استبدال الشعار' : 'رفع شعار مخصص' }}
                </label>
                <p class="mt-1 text-xs leading-6 text-slate-500">
                    JPG أو PNG أو WebP، بحد أقصى 5 ميجابايت. يُحفظ الشعار بأبعاده الأصلية دون قص، ويُحذف الملف السابق بعد نجاح الرفع.
                </p>

                <div class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-center">
                    <input
                        id="site-logo"
                        name="logo"
                        type="file"
                        required
                        accept="image/jpeg,image/png,image/webp"
                        class="block w-full text-sm text-slate-600 file:me-3 file:rounded-lg file:border-0 file:bg-brand-ink file:px-3 file:py-2 file:text-xs file:font-bold file:text-white hover:file:bg-brand-ink/90"
                    >
                    <button type="submit" class="btn btn-secondary shrink-0">
                        {{ $hasCustomLogo ? 'استبدال الشعار' : 'رفع الشعار' }}
                    </button>
                </div>

                @error('logo')
                    <p class="mt-2 text-xs font-bold text-danger">{{ $message }}</p>
                @enderror
            </form>

            @if ($hasCustomLogo)
                <x-confirm-form
                    :action="route('admin.settings.logo.destroy')"
                    title="استعادة الشعار الافتراضي"
                    message="سيُحذف ملف الشعار المخصص من التخزين ويُستخدم شعار Matrix Wave الرسمي بدلًا منه."
                    label="استعادة الشعار الافتراضي"
                />
            @endif
        </div>
    </div>
</section>
