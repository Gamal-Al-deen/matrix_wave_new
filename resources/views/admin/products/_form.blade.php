@props([
    'product' => null,
    'categories',
])

@php
    $currency = \App\Support\Currency::code();
    $statuses = \App\Enums\ProductStatus::cases();
@endphp

<div class="grid gap-5 sm:grid-cols-2">
    <div class="sm:col-span-2">
        <x-form-field label="اسم المنتج" name="name" :required="true">
            <input
                id="name"
                name="name"
                type="text"
                value="{{ old('name', $product?->name) }}"
                required
                maxlength="200"
                autofocus
                class="input"
            >
        </x-form-field>
    </div>

    <x-form-field label="الرابط (Slug)" name="slug" hint="اتركه فارغًا لتوليده تلقائيًا من الاسم.">
        <input
            id="slug"
            name="slug"
            type="text"
            value="{{ old('slug', $product?->slug) }}"
            maxlength="220"
            dir="ltr"
            class="input text-start"
        >
    </x-form-field>

    <x-form-field label="التصنيف" name="category_id" :required="true">
        <select id="category_id" name="category_id" required class="input">
            <option value="">اختر التصنيف</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected((string) old('category_id', $product?->category_id) === (string) $category->id)>
                    {{ $category->name }}{{ $category->is_active ? '' : ' (معطّل)' }}
                </option>
            @endforeach
        </select>
    </x-form-field>

    <x-form-field label="السعر" name="price" :required="true" :hint="'بالعملة الحالية: '.$currency">
        <div class="relative">
            <input
                id="price"
                name="price"
                type="number"
                value="{{ old('price', $product?->price) }}"
                required
                min="0"
                step="0.01"
                inputmode="decimal"
                class="input pe-16"
            >
            <span class="pointer-events-none absolute inset-y-0 end-4 flex items-center text-xs font-bold text-slate-500" dir="ltr">
                {{ $currency }}
            </span>
        </div>
    </x-form-field>

    <x-form-field label="حالة التوفر" name="status" :required="true">
        <select id="status" name="status" required class="input">
            @foreach ($statuses as $status)
                <option value="{{ $status->value }}" @selected(old('status', $product?->status?->value ?? 'available') === $status->value)>
                    {{ $status->label() }}
                </option>
            @endforeach
        </select>
    </x-form-field>

    <div class="sm:col-span-2">
        <x-form-field label="وصف المنتج" name="description">
            <textarea
                id="description"
                name="description"
                rows="5"
                maxlength="10000"
                class="input"
            >{{ old('description', $product?->description) }}</textarea>
        </x-form-field>
    </div>
</div>
