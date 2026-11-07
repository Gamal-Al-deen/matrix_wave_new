@extends('layouts.app')

@section('title', 'المنتجات')
@section('meta_description', 'تصفح منتجات Matrix Wave وابحث حسب الاسم أو التصنيف ورتّب حسب السعر.')

@section('content')
    @php
        $hasFilters = $query !== '' || $selectedCategory !== null || $sort !== 'newest';
        $sortOptions = [
            'newest' => 'الأحدث',
            'price_asc' => 'السعر: من الأقل إلى الأعلى',
            'price_desc' => 'السعر: من الأعلى إلى الأقل',
        ];
    @endphp

    <div class="container-page py-7 sm:py-10">
        <x-breadcrumbs :items="[
            ['label' => 'الرئيسية', 'url' => route('home')],
            ['label' => 'المنتجات'],
        ]" />

        <x-section-heading
            title="المنتجات"
            description="ابحث عن منتج بالاسم، أو اختر تصنيفًا، ثم رتّب النتائج كما يناسبك."
            level="h1"
        />

        <form method="GET" action="{{ route('products.index') }}" class="card p-4 sm:p-5" role="search" aria-label="البحث في المنتجات وتصفيتها">
            <div class="grid gap-4 lg:grid-cols-[1.4fr_1fr_1fr_auto] lg:items-end">
                <div class="space-y-1.5">
                    <label for="catalog-q" class="flex items-center gap-1.5 text-xs font-bold text-ink">
                        <x-icon name="search" class="h-4 w-4 text-brand-teal" />
                        ابحث بالاسم
                    </label>
                    <div class="relative">
                        <x-icon name="search" class="pointer-events-none absolute start-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                        <input
                            id="catalog-q"
                            name="q"
                            type="search"
                            value="{{ $query }}"
                            maxlength="100"
                            placeholder="اكتب اسم المنتج..."
                            class="input ps-11"
                        >
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label for="catalog-category" class="flex items-center gap-1.5 text-xs font-bold text-ink">
                        <x-icon name="tags" class="h-4 w-4 text-brand-teal" />
                        التصنيف
                    </label>
                    <select id="catalog-category" name="category" class="input">
                        <option value="">كل التصنيفات</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->slug }}" @selected($selectedCategory?->id === $category->id)>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="space-y-1.5">
                    <label for="catalog-sort" class="flex items-center gap-1.5 text-xs font-bold text-ink">
                        <x-icon name="filter" class="h-4 w-4 text-brand-teal" />
                        الترتيب
                    </label>
                    <select id="catalog-sort" name="sort" class="input">
                        @foreach ($sortOptions as $value => $label)
                            <option value="{{ $value }}" @selected($sort === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <button type="submit" class="btn btn-primary flex-1 sm:flex-none">
                        <x-icon name="search" class="h-4 w-4" />
                        تطبيق
                    </button>
                    @if ($hasFilters)
                        <a href="{{ route('products.index') }}" class="btn btn-outline flex-1 sm:flex-none">
                            <x-icon name="refresh" class="h-4 w-4" />
                            إعادة تعيين
                        </a>
                    @endif
                </div>
            </div>
        </form>

        <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
            <p class="inline-flex items-center gap-2 rounded-full border border-brand-ink/10 bg-white px-3 py-1.5 text-sm font-bold text-slate-600">
                <x-icon name="box" class="h-4 w-4 text-brand-teal" />
                {{ $products->total() > 0 ? 'عدد النتائج: '.$products->total() : 'لا توجد نتائج' }}
            </p>

            @if ($query !== '')
                <p class="text-xs text-slate-500">
                    نتائج البحث عن: <span class="font-bold text-brand-ink">«{{ $query }}»</span>
                </p>
            @elseif ($selectedCategory)
                <p class="text-xs text-slate-500">
                    التصنيف المحدد: <span class="font-bold text-brand-ink">{{ $selectedCategory->name }}</span>
                </p>
            @endif
        </div>

        @if ($products->isEmpty())
            <div class="mt-6">
                <x-empty-state
                    title="لا توجد منتجات مطابقة"
                    description="جرّب كلمة بحث أخرى أو اختر تصنيفًا مختلفًا، أو أعد تعيين عوامل التصفية."
                    action-label="إعادة تعيين البحث"
                    action-icon="refresh"
                    :action-url="route('products.index')"
                />
            </div>
        @else
            <div class="mt-6 grid grid-cols-2 gap-3 sm:gap-5 md:grid-cols-3 xl:grid-cols-4">
                @foreach ($products as $product)
                    <x-product-card :product="$product" :currency="$currency" />
                @endforeach
            </div>

            <div class="mt-8">{{ $products->links() }}</div>
        @endif
    </div>
@endsection
