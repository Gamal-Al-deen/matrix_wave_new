@props([
    'wallet',
])

@php
    $hasIcon = filled($wallet->icon_public_url);
@endphp

<section class="card p-5 sm:p-6" aria-labelledby="wallet-icon-title">
    <h2 id="wallet-icon-title" class="flex items-center gap-2 text-lg font-extrabold text-brand-ink">
        <span class="grid h-9 w-9 place-items-center rounded-xl bg-brand-teal/15 text-brand-ink">
            <x-icon name="wallet" class="h-5 w-5" />
        </span>
        أيقونة المحفظة
    </h2>
    <p class="mt-1 text-sm leading-7 text-slate-600">
        أيقونة اختيارية تظهر بجانب اسم المحفظة في صفحة إتمام الطلب. استخدم شعارًا واضحًا ومربعًا إن أمكن.
    </p>

    <div class="mt-5 flex flex-col gap-5 sm:flex-row sm:items-start">
        <div class="grid h-24 w-24 shrink-0 place-items-center overflow-hidden rounded-2xl border border-brand-ink/10 bg-surface-muted">
            @if ($hasIcon)
                <img
                    src="{{ $wallet->icon_public_url }}"
                    alt="أيقونة محفظة {{ $wallet->name }}"
                    class="h-full w-full object-contain p-2"
                >
            @else
                <div class="text-center text-xs text-slate-500">
                    <x-icon name="wallet" class="mx-auto h-8 w-8 text-slate-400" />
                    <span class="mt-2 block">لا توجد أيقونة</span>
                </div>
            @endif
        </div>

        <div class="min-w-0 flex-1 space-y-4">
            <form
                method="POST"
                action="{{ route('admin.wallets.icon.store', $wallet) }}"
                enctype="multipart/form-data"
                class="rounded-2xl border border-dashed border-brand-ink/20 bg-surface-muted p-4"
            >
                @csrf

                <label for="wallet-icon" class="block text-sm font-bold text-ink">
                    {{ $hasIcon ? 'استبدال الأيقونة' : 'رفع أيقونة' }}
                </label>
                <p class="mt-1 text-xs leading-6 text-slate-500">
                    JPG أو PNG أو WebP، بحد أقصى 5 ميجابايت. عند الاستبدال يُحذف الملف السابق بعد نجاح الرفع.
                </p>

                <div class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-center">
                    <input
                        id="wallet-icon"
                        name="icon"
                        type="file"
                        required
                        accept="image/jpeg,image/png,image/webp"
                        class="block min-h-11 w-full text-sm text-slate-600 file:me-3 file:rounded-lg file:border-0 file:bg-brand-ink file:px-3 file:py-2 file:text-xs file:font-bold file:text-white hover:file:bg-brand-ink/90"
                    >
                    <button type="submit" class="btn btn-secondary shrink-0">
                        <x-icon name="upload" class="h-4 w-4" />
                        {{ $hasIcon ? 'استبدال الأيقونة' : 'رفع الأيقونة' }}
                    </button>
                </div>

                @error('icon')
                    <p class="mt-2 text-xs font-bold text-danger">{{ $message }}</p>
                @enderror
            </form>

            @if ($hasIcon)
                <x-confirm-form
                    :action="route('admin.wallets.icon.destroy', $wallet)"
                    title="حذف أيقونة المحفظة"
                    message="سيتم حذف ملف الأيقونة من التخزين. ستبقى المحفظة وبياناتها كما هي."
                    label="حذف الأيقونة"
                />
            @endif
        </div>
    </div>
</section>
