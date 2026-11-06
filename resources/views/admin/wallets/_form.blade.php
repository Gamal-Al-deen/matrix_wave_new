@props([
    'wallet' => null,
])

<div class="grid gap-5 sm:grid-cols-2">
    <div class="sm:col-span-2">
        <x-form-field label="اسم المحفظة" name="name" :required="true">
            <input
                id="name"
                name="name"
                type="text"
                value="{{ old('name', $wallet?->name) }}"
                required
                maxlength="120"
                autofocus
                class="input"
            >
        </x-form-field>
    </div>

    <div class="sm:col-span-2">
        <x-form-field
            label="معرف التحويل"
            name="account_identifier"
            :required="true"
            hint="رقم المحفظة أو البريد أو المعرف الذي يُحوّل إليه. لا تُدخل كلمات مرور أو أرقام بطاقات دفع."
        >
            <input
                id="account_identifier"
                name="account_identifier"
                type="text"
                value="{{ old('account_identifier', $wallet?->account_identifier) }}"
                required
                maxlength="255"
                dir="auto"
                autocomplete="off"
                class="input"
            >
        </x-form-field>
    </div>

    <x-form-field label="ترتيب العرض" name="sort_order" :required="true" hint="الأرقام الأصغر تظهر أولًا. استخدم 0 إذا لم تكن هناك أولوية.">
        <input
            id="sort_order"
            name="sort_order"
            type="number"
            value="{{ old('sort_order', $wallet?->sort_order ?? 0) }}"
            required
            min="0"
            max="2147483647"
            step="1"
            inputmode="numeric"
            class="input"
        >
    </x-form-field>

    <x-form-field label="الحالة" name="is_active" hint="المحافظ المفعّلة فقط تظهر للعملاء عند إتمام الطلب.">
        <label class="flex min-h-11 items-center gap-3 rounded-xl border border-brand-ink/15 bg-white px-4 py-2.5">
            <input
                id="is_active"
                name="is_active"
                type="checkbox"
                value="1"
                @checked(old('is_active', $wallet?->is_active ?? true))
                class="h-4 w-4 rounded border-brand-ink/30 text-brand-teal focus:ring-brand-teal"
            >
            <span class="text-sm font-bold text-ink">مفعّلة ومتاحة للاختيار</span>
        </label>
    </x-form-field>

    @if ($wallet === null)
        <div class="sm:col-span-2">
            <x-alert type="info">
                بعد حفظ المحفظة ستنتقل إلى صفحة التعديل حيث يمكنك رفع أيقونتها.
            </x-alert>
        </div>
    @endif
</div>
