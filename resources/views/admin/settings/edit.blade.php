@extends('layouts.admin')

@section('title', 'إعدادات الموقع')

@section('content')
    <div class="space-y-6">
        <x-breadcrumbs :items="[
            ['label' => 'لوحة التحكم', 'url' => route('admin.dashboard')],
            ['label' => 'إعدادات الموقع'],
        ]" />

        <x-section-heading
            title="إعدادات الموقع"
            description="تحكم في هوية الموقع وعملته وبيانات التواصل. تنعكس هذه الإعدادات على الواجهة العامة والفواتير."
        />

        <x-alert type="warning">
            تغيير عملة الموقع لا يحوّل أسعار المنتجات الحالية ولا يغيّر الطلبات السابقة. بعد تغيير العملة يجب مراجعة أسعار جميع المنتجات يدويًا.
        </x-alert>

        @include('admin.settings._logo', ['settings' => $settings])

        <div class="card p-5 sm:p-6">
            <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-6">
                @csrf
                @method('PUT')

                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <x-form-field label="اسم الموقع" name="site_name" :required="true">
                            <input
                                id="site_name"
                                name="site_name"
                                type="text"
                                value="{{ old('site_name', $settings->site_name) }}"
                                required
                                maxlength="150"
                                autocomplete="organization"
                                class="input"
                            >
                        </x-form-field>
                    </div>

                    <div class="sm:col-span-2">
                        <x-form-field
                            label="وصف الموقع"
                            name="description"
                            hint="وصف قصير يظهر في تذييل الصفحة ووسم وصف الصفحة الرئيسية. اتركه فارغًا لاستخدام الوصف الافتراضي."
                        >
                            <textarea
                                id="description"
                                name="description"
                                rows="4"
                                maxlength="2000"
                                class="input"
                            >{{ old('description', $settings->description) }}</textarea>
                        </x-form-field>
                    </div>

                    <x-form-field
                        label="عملة الموقع"
                        name="currency_code"
                        :required="true"
                        hint="رمز لاتيني من 3 إلى 12 حرفًا، مثل USD أو SAR. تُعرض به أسعار المنتجات الجديدة والطلبات الجديدة."
                    >
                        <input
                            id="currency_code"
                            name="currency_code"
                            type="text"
                            value="{{ old('currency_code', $settings->currency_code) }}"
                            required
                            maxlength="12"
                            dir="ltr"
                            autocapitalize="characters"
                            class="input"
                        >
                    </x-form-field>

                    <x-form-field
                        label="رقم واتساب"
                        name="whatsapp_number"
                        hint="اختياري. يُستخدم لزر إرسال تفاصيل الطلب يدويًا عبر واتساب. أدخل الرقم بصيغة دولية مع رمز الدولة إن أمكن؛ تُزال الرموز عند بناء الرابط."
                    >
                        <input
                            id="whatsapp_number"
                            name="whatsapp_number"
                            type="text"
                            value="{{ old('whatsapp_number', $settings->whatsapp_number) }}"
                            maxlength="30"
                            dir="ltr"
                            inputmode="tel"
                            autocomplete="off"
                            class="input"
                        >
                    </x-form-field>

                    <x-form-field label="هاتف التواصل" name="contact_phone" hint="اختياري. يظهر في تذييل الصفحة للتواصل المباشر.">
                        <input
                            id="contact_phone"
                            name="contact_phone"
                            type="text"
                            value="{{ old('contact_phone', $settings->contact_phone) }}"
                            maxlength="40"
                            dir="ltr"
                            inputmode="tel"
                            autocomplete="tel"
                            class="input"
                        >
                    </x-form-field>

                    <x-form-field label="بريد التواصل" name="contact_email" hint="اختياري. يظهر في تذييل الصفحة.">
                        <input
                            id="contact_email"
                            name="contact_email"
                            type="email"
                            value="{{ old('contact_email', $settings->contact_email) }}"
                            maxlength="255"
                            dir="ltr"
                            autocomplete="email"
                            class="input"
                        >
                    </x-form-field>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <button type="submit" class="btn btn-primary">
                        <x-icon name="save" class="h-4 w-4" />
                        حفظ الإعدادات
                    </button>
                    <a href="{{ route('admin.dashboard') }}" class="btn btn-outline">
                        <x-icon name="close" class="h-4 w-4" />
                        إلغاء
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection
