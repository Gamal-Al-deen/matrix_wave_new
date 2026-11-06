@props([
    'banner',
])

@php
    $hasImage = filled($banner->image_public_url);
@endphp

<section class="card p-5 sm:p-6" aria-labelledby="banner-image-title">
    <h2 id="banner-image-title" class="text-lg font-extrabold text-brand-ink">صورة البانر</h2>
    <p class="mt-1 text-sm leading-7 text-slate-600">
        تُستخدم الصورة الحالية في الصفحة الرئيسية عند تحقق شروط النشر. لا يمكن حذف الصورة وحدها لأن البانر يتطلب صورة؛
        يمكنك استبدالها أو حذف البانر بالكامل.
    </p>

    <div class="mt-5 overflow-hidden rounded-2xl border border-brand-ink/10 bg-surface-muted">
        @if ($hasImage)
            <img
                src="{{ $banner->image_public_url }}"
                alt="{{ $banner->alt_text }}"
                class="aspect-[16/6] w-full object-cover"
            >
        @else
            <div class="grid aspect-[16/6] w-full place-items-center text-sm text-slate-500">
                لا توجد صورة متاحة
            </div>
        @endif
    </div>

    <form
        method="POST"
        action="{{ route('admin.banners.image.replace', $banner) }}"
        enctype="multipart/form-data"
        class="mt-5 rounded-2xl border border-dashed border-brand-ink/20 bg-surface-muted p-4"
    >
        @csrf

        <label for="banner-image" class="block text-sm font-bold text-ink">استبدال الصورة</label>
        <p class="mt-1 text-xs leading-6 text-slate-500">
            JPG أو PNG أو WebP، بحد أقصى 5 ميجابايت. يُحذف الملف السابق بعد نجاح رفع الصورة الجديدة.
        </p>

        <div class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-center">
            <input
                id="banner-image"
                name="image"
                type="file"
                required
                accept="image/jpeg,image/png,image/webp"
                class="block w-full text-sm text-slate-600 file:me-3 file:rounded-lg file:border-0 file:bg-brand-ink file:px-3 file:py-2 file:text-xs file:font-bold file:text-white hover:file:bg-brand-ink/90"
            >
            <button type="submit" class="btn btn-secondary shrink-0">استبدال الصورة</button>
        </div>

        @error('image')
            <p class="mt-2 text-xs font-bold text-danger">{{ $message }}</p>
        @enderror
    </form>
</section>
