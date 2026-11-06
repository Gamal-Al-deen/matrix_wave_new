@props([
    'socialLink' => null,
    'platforms',
])

<div class="grid gap-5 sm:grid-cols-2">
    <x-form-field label="المنصة" name="platform" :required="true">
        <select id="platform" name="platform" required class="input">
            <option value="">اختر المنصة</option>
            @foreach ($platforms as $platform)
                <option value="{{ $platform->value }}" @selected(old('platform', $socialLink?->platform?->value) === $platform->value)>
                    {{ $platform->label() }}
                </option>
            @endforeach
        </select>
    </x-form-field>

    <x-form-field label="ترتيب العرض" name="sort_order" :required="true" hint="الأرقام الأصغر تظهر أولًا في التذييل.">
        <input
            id="sort_order"
            name="sort_order"
            type="number"
            value="{{ old('sort_order', $socialLink?->sort_order ?? 0) }}"
            required
            min="0"
            max="2147483647"
            step="1"
            inputmode="numeric"
            class="input"
        >
    </x-form-field>

    <div class="sm:col-span-2">
        <x-form-field
            label="رابط المنصة"
            name="url"
            :required="true"
            hint="أدخل رابطًا كاملًا يبدأ بـ https:// أو http://. لا تُدخل رابطًا غير مؤكد أو وهميًا."
        >
            <input
                id="url"
                name="url"
                type="url"
                value="{{ old('url', $socialLink?->url) }}"
                required
                maxlength="1000"
                dir="ltr"
                inputmode="url"
                autocomplete="off"
                placeholder="https://example.com/profile"
                class="input"
            >
        </x-form-field>
    </div>

    <div class="sm:col-span-2">
        <x-form-field label="الحالة" name="is_active" hint="تظهر الروابط المفعّلة فقط في تذييل الموقع.">
            <label class="flex min-h-11 items-center gap-3 rounded-xl border border-brand-ink/15 bg-white px-4 py-2.5">
                <input
                    id="is_active"
                    name="is_active"
                    type="checkbox"
                    value="1"
                    @checked(old('is_active', $socialLink?->is_active ?? true))
                    class="h-4 w-4 rounded border-brand-ink/30 text-brand-teal focus:ring-brand-teal"
                >
                <span class="text-sm font-bold text-ink">مفعّل ويظهر للزوار</span>
            </label>
        </x-form-field>
    </div>
</div>
