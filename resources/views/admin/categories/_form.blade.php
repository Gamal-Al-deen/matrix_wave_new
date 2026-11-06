@props([
    'category' => null,
])

<div class="grid gap-5 sm:grid-cols-2">
    <div class="sm:col-span-2">
        <x-form-field label="اسم التصنيف" name="name" :required="true">
            <input
                id="name"
                name="name"
                type="text"
                value="{{ old('name', $category?->name) }}"
                required
                maxlength="150"
                autofocus
                class="input"
            >
        </x-form-field>
    </div>

    <x-form-field label="الرابط (Slug)" name="slug" hint="أحرف إنجليزية وأرقام وشرطات فقط. اتركه فارغًا لتوليده تلقائيًا.">
        <input
            id="slug"
            name="slug"
            type="text"
            value="{{ old('slug', $category?->slug) }}"
            maxlength="180"
            dir="ltr"
            class="input text-start"
        >
    </x-form-field>

    <x-form-field label="الحالة" name="is_active">
        <label class="flex min-h-11 items-center gap-3 rounded-xl border border-brand-ink/15 bg-white px-4 py-2.5">
            <input
                id="is_active"
                name="is_active"
                type="checkbox"
                value="1"
                @checked(old('is_active', $category?->is_active ?? true))
                class="h-4 w-4 rounded border-brand-ink/30 text-brand-teal focus:ring-brand-teal"
            >
            <span class="text-sm font-bold text-ink">مفعّل</span>
        </label>
    </x-form-field>

    <div class="sm:col-span-2">
        <x-form-field label="الوصف" name="description" hint="اختياري، يظهر في صفحة التصنيف عند تفعيلها.">
            <textarea
                id="description"
                name="description"
                rows="4"
                maxlength="5000"
                class="input"
            >{{ old('description', $category?->description) }}</textarea>
        </x-form-field>
    </div>
</div>
