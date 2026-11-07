@php
    $maxImages = (int) config('media.max_product_images', 10);
    $draftImages = $draftImages ?? [];
@endphp

<div class="space-y-3" data-draft-images>
    <div>
        <span class="block text-sm font-bold text-ink">
            صور المنتج
            <span class="text-danger" aria-hidden="true">*</span>
        </span>
        <p class="mt-1 text-xs leading-6 text-slate-500">
            JPG أو PNG أو WebP بحد أقصى 5 ميجابايت للصورة. يبدأ الرفع تلقائيًا بمجرد اختيار الصورة.
        </p>
    </div>

    <label
        for="product-image-input"
        class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-2xl border border-dashed border-brand-ink/20 bg-surface-muted p-5 text-center transition hover:border-brand-teal"
    >
        <span class="grid h-11 w-11 place-items-center rounded-xl bg-brand-teal/15 text-brand-ink" aria-hidden="true">
            <x-icon name="upload" class="h-5 w-5" />
        </span>
        <span class="text-sm font-bold text-brand-ink">اختر صورة المنتج</span>
        <span class="text-xs text-slate-500">يمكن اختيار عدة صور معًا (حتى {{ $maxImages }} صور).</span>
        <input
            id="product-image-input"
            type="file"
            accept="image/jpeg,image/png,image/webp"
            multiple
            class="sr-only"
            data-draft-input
        >
    </label>

    <p class="hidden text-xs font-bold text-danger" data-draft-error role="alert"></p>

    <ul class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4" data-draft-list>
        @foreach ($draftImages as $key => $draft)
            <li
                class="relative overflow-hidden rounded-2xl border border-brand-ink/10 bg-white p-2"
                data-draft-item
                data-key="{{ $key }}"
            >
                <div class="aspect-[4/3] overflow-hidden rounded-xl bg-surface-muted">
                    @if (! empty($draft['public_url']))
                        <img src="{{ $draft['public_url'] }}" alt="" loading="lazy" class="h-full w-full object-cover">
                    @endif
                </div>

                <p class="mt-2 truncate text-[0.7rem] text-slate-500" dir="ltr" title="{{ $draft['original_name'] ?? '' }}">
                    {{ $draft['original_name'] ?? '' }}
                </p>

                <button
                    type="button"
                    class="absolute end-2 top-2 grid h-7 w-7 place-items-center rounded-full bg-brand-ink/80 text-white hover:bg-brand-ink"
                    data-draft-remove
                    aria-label="حذف الصورة"
                >
                    <x-icon name="close" class="h-4 w-4" />
                </button>

                <input type="hidden" name="draft_images[]" value="{{ $key }}" data-draft-key-input>
            </li>
        @endforeach
    </ul>

    <p class="text-xs font-bold text-slate-500" data-draft-status hidden aria-live="polite"></p>
</div>
