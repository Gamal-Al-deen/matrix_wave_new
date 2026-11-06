@props([
    'product',
    'currency' => 'USD',
])

@php
    $image = $product->images->first();
    $available = $product->status->isOrderable();
@endphp

<article class="card card-hover group flex h-full flex-col overflow-hidden">
    <a
        href="{{ route('products.show', $product->slug) }}"
        class="relative block aspect-[4/3] overflow-hidden bg-surface-muted"
        aria-label="عرض تفاصيل {{ $product->name }}"
    >
        @if ($image?->public_url)
            <img
                src="{{ $image->public_url }}"
                alt="{{ $image->alt_text ?: $product->name }}"
                class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.04]"
                loading="lazy"
                decoding="async"
            >
        @else
            <span class="grid h-full place-items-center text-slate-400" aria-hidden="true">
                <x-icon name="image" class="h-12 w-12" />
            </span>
        @endif

        <span @class([
            'badge absolute start-3 top-3 gap-1.5 border border-white/20 shadow-sm backdrop-blur',
            'bg-success/95 text-white' => $available,
            'bg-warning/95 text-white' => ! $available,
        ])>
            <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true"></span>
            {{ $product->status->label() }}
        </span>
    </a>

    <div class="flex flex-1 flex-col p-4">
        @if ($product->category?->name)
            <p class="flex items-center gap-1 text-xs font-bold text-slate-500">
                <x-icon name="tags" class="h-3.5 w-3.5 text-brand-teal" />
                {{ $product->category->name }}
            </p>
        @endif

        <h3 class="mt-1 line-clamp-2 text-base font-extrabold leading-7 text-brand-ink">
            <a href="{{ route('products.show', $product->slug) }}" class="tap-link hover:text-brand-ink/80">
                {{ $product->name }}
            </a>
        </h3>

        <p class="mt-3 text-lg font-extrabold text-brand-ink">
            <span dir="ltr">{{ number_format((float) $product->price, 2) }} {{ $currency }}</span>
        </p>

        <div class="mt-auto grid gap-2 pt-4 min-[560px]:grid-cols-2">
            <a href="{{ route('products.show', $product->slug) }}" class="btn btn-outline gap-1.5 px-3 py-2 text-xs">
                <x-icon name="eye" class="h-4 w-4" />
                التفاصيل
            </a>

            @if ($available)
                <form method="POST" action="{{ route('cart.store', $product->slug) }}" class="contents">
                    @csrf
                    <input type="hidden" name="quantity" value="1">
                    <button type="submit" class="btn btn-primary gap-1.5 px-3 py-2 text-xs" aria-label="أضف {{ $product->name }} إلى السلة">
                        <x-icon name="cart" class="h-4 w-4" />
                        أضف إلى السلة
                    </button>
                </form>
            @else
                <span class="btn cursor-not-allowed gap-1.5 bg-slate-100 px-3 py-2 text-xs text-slate-600" aria-disabled="true">
                    <x-icon name="ban" class="h-4 w-4" />
                    غير متوفر
                </span>
            @endif
        </div>
    </div>
</article>
