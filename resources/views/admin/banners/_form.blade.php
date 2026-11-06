@props([
    'banner' => null,
])

<div class="grid gap-5 sm:grid-cols-2">
    <div class="sm:col-span-2">
        <x-form-field label="عنوان البانر" name="title" hint="اختياري. يظهر فوق الصورة في الصفحة الرئيسية إن أدخلته.">
            <input
                id="title"
                name="title"
                type="text"
                value="{{ old('title', $banner?->title) }}"
                maxlength="200"
                dir="auto"
                autofocus
                class="input"
            >
        </x-form-field>
    </div>

    <div class="sm:col-span-2">
        <x-form-field
            label="النص البديل للصورة"
            name="alt_text"
            :required="true"
            hint="وصف قصير للصورة لقارئات الشاشة، ويظهر إذا تعذر تحميل الصورة."
        >
            <input
                id="alt_text"
                name="alt_text"
                type="text"
                value="{{ old('alt_text', $banner?->alt_text) }}"
                required
                maxlength="255"
                dir="auto"
                class="input"
            >
        </x-form-field>
    </div>

    @if ($banner === null)
        <div class="sm:col-span-2">
            <x-form-field
                label="صورة البانر"
                name="image"
                :required="true"
                hint="JPG أو PNG أو WebP، بحد أقصى 5 ميجابايت. استخدم صورة عريضة عالية الجودة."
            >
                <input
                    id="image"
                    name="image"
                    type="file"
                    required
                    accept="image/jpeg,image/png,image/webp"
                    class="block min-h-11 w-full text-sm text-slate-600 file:me-3 file:rounded-lg file:border-0 file:bg-brand-ink file:px-3 file:py-2 file:text-xs file:font-bold file:text-white hover:file:bg-brand-ink/90"
                >
            </x-form-field>
        </div>
    @endif

    <x-form-field label="ترتيب العرض" name="sort_order" :required="true" hint="الأرقام الأصغر تظهر أولًا في الصفحة الرئيسية.">
        <input
            id="sort_order"
            name="sort_order"
            type="number"
            value="{{ old('sort_order', $banner?->sort_order ?? 0) }}"
            required
            min="0"
            max="2147483647"
            step="1"
            inputmode="numeric"
            class="input"
        >
    </x-form-field>

    <x-form-field label="الحالة" name="is_active" hint="يجب أن يكون البانر مفعّلًا ويقع الوقت الحالي داخل فترة النشر ليظهر للزوار.">
        <label class="flex min-h-11 items-center gap-3 rounded-xl border border-brand-ink/15 bg-white px-4 py-2.5">
            <input
                id="is_active"
                name="is_active"
                type="checkbox"
                value="1"
                @checked(old('is_active', $banner?->is_active ?? false))
                class="h-4 w-4 rounded border-brand-ink/30 text-brand-teal focus:ring-brand-teal"
            >
            <span class="text-sm font-bold text-ink">مفعّل للنشر</span>
        </label>
    </x-form-field>

    <x-form-field label="بداية النشر" name="starts_at" hint="اختياري. يُفسَّر الوقت بتوقيت UTC. اتركه فارغًا للبدء فورًا.">
        <input
            id="starts_at"
            name="starts_at"
            type="datetime-local"
            value="{{ old('starts_at', $banner?->starts_at?->format('Y-m-d\TH:i')) }}"
            class="input"
        >
    </x-form-field>

    <x-form-field label="نهاية النشر" name="ends_at" hint="اختياري. يُفسَّر الوقت بتوقيت UTC. اتركه فارغًا لعدم تحديد نهاية.">
        <input
            id="ends_at"
            name="ends_at"
            type="datetime-local"
            value="{{ old('ends_at', $banner?->ends_at?->format('Y-m-d\TH:i')) }}"
            class="input"
        >
    </x-form-field>

    <div class="sm:col-span-2">
        <x-alert type="info">
            البانر المفعّل يظهر فقط داخل فترة النشر. إذا انتهت الفترة أو لم يبدأ بعد، يبقى محفوظًا في الإدارة لكنه لا يظهر في الموقع.
        </x-alert>
    </div>
</div>
