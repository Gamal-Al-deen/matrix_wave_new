@props([
    'product',
])

@php
    $limit = (int) config('media.max_product_images', 10);
    $count = $product->images->count();
@endphp

<section class="card p-5 sm:p-6" aria-labelledby="product-images-title">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h2 id="product-images-title" class="text-lg font-extrabold text-brand-ink">صور المنتج</h2>
            <p class="mt-1 text-sm text-slate-600">
                {{ $count }} من {{ $limit }} صور. الترتيب يحدد ترتيب العرض في صفحات المتجر.
            </p>
        </div>
    </div>

    @if ($count < $limit)
        <form
            method="POST"
            action="{{ route('admin.products.images.store', $product) }}"
            enctype="multipart/form-data"
            class="mt-5 rounded-2xl border border-dashed border-brand-ink/20 bg-surface-muted p-4"
        >
            @csrf

            <label for="images" class="block text-sm font-bold text-ink">إضافة صور</label>
            <p class="mt-1 text-xs leading-6 text-slate-500">
                JPG أو PNG أو WebP، بحد أقصى 5 ميجابايت للصورة. يمكن اختيار عدة صور معًا.
            </p>

            <div class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-center">
                <input
                    id="images"
                    name="images[]"
                    type="file"
                    multiple
                    required
                    accept="image/jpeg,image/png,image/webp"
                    class="block w-full text-sm text-slate-600 file:me-3 file:rounded-lg file:border-0 file:bg-brand-ink file:px-3 file:py-2 file:text-xs file:font-bold file:text-white hover:file:bg-brand-ink/90"
                >
                <button type="submit" class="btn btn-secondary shrink-0">رفع الصور</button>
            </div>

            @error('images')
                <p class="mt-2 text-xs font-bold text-danger">{{ $message }}</p>
            @enderror

            @error('images.*')
                <p class="mt-2 text-xs font-bold text-danger">{{ $message }}</p>
            @enderror
        </form>
    @else
        <div class="mt-5">
            <x-alert type="info">
                بلغ المنتج الحد الأقصى لعدد الصور ({{ $limit }}). احذف صورة أو استبدلها لإضافة أخرى.
            </x-alert>
        </div>
    @endif

    @if ($count > 0)
        <ul class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($product->images as $image)
                <li class="rounded-2xl border border-brand-ink/10 bg-white p-3">
                    <div class="aspect-[4/3] overflow-hidden rounded-xl bg-surface-muted">
                        @if ($image->public_url)
                            <img
                                src="{{ $image->public_url }}"
                                alt="{{ $image->alt_text ?: $product->name }}"
                                class="h-full w-full object-cover"
                                loading="lazy"
                            >
                        @else
                            <div class="grid h-full place-items-center text-xs text-slate-500">لا توجد معاينة</div>
                        @endif
                    </div>

                    <p class="mt-2 truncate text-xs text-slate-500" dir="ltr" title="{{ $image->original_name }}">
                        {{ $image->original_name }}
                    </p>

                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        <form method="POST" action="{{ route('admin.products.images.move', [$product, $image]) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="direction" value="up">
                            <button
                                type="submit"
                                class="rounded-lg border border-brand-ink/15 px-2 py-1 text-xs font-bold text-brand-ink hover:border-brand-teal disabled:cursor-not-allowed disabled:opacity-40"
                                @disabled($loop->first)
                            >
                                أعلى
                            </button>
                        </form>

                        <form method="POST" action="{{ route('admin.products.images.move', [$product, $image]) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="direction" value="down">
                            <button
                                type="submit"
                                class="rounded-lg border border-brand-ink/15 px-2 py-1 text-xs font-bold text-brand-ink hover:border-brand-teal disabled:cursor-not-allowed disabled:opacity-40"
                                @disabled($loop->last)
                            >
                                أسفل
                            </button>
                        </form>

                        <span class="text-xs text-slate-400" dir="ltr">#{{ $image->sort_order }}</span>

                        <x-confirm-form
                            :action="route('admin.products.images.destroy', [$product, $image])"
                            title="حذف الصورة"
                            message="سيتم حذف ملف الصورة من التخزين وبياناتها من المنتج."
                            label="حذف"
                        />
                    </div>

                    <form
                        method="POST"
                        action="{{ route('admin.products.images.replace', [$product, $image]) }}"
                        enctype="multipart/form-data"
                        class="mt-3 flex items-center gap-2"
                    >
                        @csrf

                        <label for="replace-image-{{ $image->id }}" class="sr-only">استبدال الصورة</label>
                        <input
                            id="replace-image-{{ $image->id }}"
                            name="image"
                            type="file"
                            required
                            accept="image/jpeg,image/png,image/webp"
                            class="block w-full text-xs text-slate-600 file:me-2 file:rounded-md file:border-0 file:bg-surface-muted file:px-2 file:py-1.5 file:text-xs file:font-bold file:text-brand-ink hover:file:bg-brand-ink/10"
                        >
                        <button type="submit" class="btn btn-outline shrink-0 px-2.5 py-1.5 text-xs">استبدال</button>
                    </form>
                </li>
            @endforeach
        </ul>
    @else
        <p class="mt-5 text-sm text-slate-500">لا توجد صور لهذا المنتج بعد.</p>
    @endif
</section>
