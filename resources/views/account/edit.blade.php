@extends('layouts.app')

@section('title', 'حسابي')
@section('meta_description', 'راجع بيانات حسابك وحدّث الاسم ورقم الهاتف والعنوان الافتراضي.')

@section('content')
    <div class="container-page py-8 sm:py-12">
        <x-breadcrumbs :items="[
            ['label' => 'الرئيسية', 'url' => route('home')],
            ['label' => 'حسابي'],
        ]" />

        <x-section-heading
            title="حسابي"
            description="حدّث بيانات التواصل والعنوان الافتراضي المستخدم عند إتمام الطلب."
            level="h1"
        />

        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
            <section class="card" aria-labelledby="account-details-heading">
                <div class="flex items-start gap-3 border-b border-brand-ink/10 p-5">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-brand-teal/15 text-brand-ink">
                        <x-icon name="user" class="h-5 w-5" />
                    </span>
                    <div>
                        <h2 id="account-details-heading" class="text-lg font-extrabold text-brand-ink">بيانات الحساب</h2>
                        <p class="mt-1 text-sm leading-7 text-slate-600">
                            البريد الإلكتروني وكلمة المرور غير قابلين للتعديل من هذه الصفحة.
                        </p>
                    </div>
                </div>
                

                <form method="POST" action="{{ route('account.update') }}" class="space-y-5 p-5">
                    @csrf
                    @method('PATCH')

                    <x-form-field label="الاسم الكامل" name="name" :required="true">
                        <input
                            id="name"
                            name="name"
                            type="text"
                            value="{{ old('name', $user->name) }}"
                            required
                            maxlength="150"
                            autocomplete="name"
                            @error('name') aria-invalid="true" aria-describedby="name-error" @enderror
                            class="input @error('name') border-danger @enderror"
                        >
                    </x-form-field>

                    <x-form-field label="البريد الإلكتروني" name="email" hint="لا يمكن تغيير البريد الإلكتروني من هنا.">
                        <input
                            id="email"
                            type="email"
                            value="{{ $user->email }}"
                            disabled
                            dir="ltr"
                            class="input text-start disabled:cursor-not-allowed disabled:bg-surface-muted disabled:text-slate-500"
                        >
                    </x-form-field>

                    <x-form-field
                        label="رقم الهاتف"
                        name="phone"
                        :hint="$phoneNeedsUpdate
                            ? 'الرقم المحفوظ حاليًا بصيغة قديمة. احفظه كما هو أو استبدله برقم يمني محلي من 9 أرقام يبدأ بـ 70 أو 71 أو 73 أو 77 أو 78، دون +967.'
                            : 'اختياري. 9 أرقام محلية تبدأ بـ 70 أو 71 أو 73 أو 77 أو 78، دون +967.'"
                    >
                        <input
                            id="phone"
                            name="phone"
                            type="tel"
                            value="{{ old('phone', $user->phone) }}"
                            @unless ($phoneNeedsUpdate) minlength="9" maxlength="9" pattern="(?:7[01378]|٧[٠١٣٧٨])[0-9٠-٩]{7}" @endunless
                            inputmode="numeric"
                            autocomplete="tel"
                            dir="ltr"
                            @error('phone') aria-invalid="true" @enderror
                            aria-describedby="phone-hint{{ $errors->has('phone') ? ' phone-error' : '' }}"
                            class="input text-start @error('phone') border-danger @enderror"
                        >
                    </x-form-field>

                    <x-form-field label="العنوان الافتراضي" name="default_address" hint="يُستخدم لتعبئة عنوان التوصيل تلقائيًا عند إتمام الطلب.">
                        <textarea
                            id="default_address"
                            name="default_address"
                            rows="4"
                            maxlength="{{ (int) config('checkout.address_max', 1000) }}"
                            autocomplete="street-address"
                            @error('default_address') aria-invalid="true" @enderror
                            aria-describedby="default_address-hint{{ $errors->has('default_address') ? ' default_address-error' : '' }}"
                            class="input @error('default_address') border-danger @enderror"
                        >{{ old('default_address', $user->default_address) }}</textarea>
                    </x-form-field>

                    <div class="flex flex-wrap items-center gap-3 border-t border-brand-ink/10 pt-5">
                        <button type="submit" class="btn btn-primary">
                            <x-icon name="save" class="h-4 w-4" />
                            حفظ التغييرات
                        </button>
                        <a href="{{ route('orders.index') }}" class="btn btn-outline">
                            <x-icon name="orders" class="h-4 w-4" />
                            طلباتي
                        </a>
                    </div>
                </form>
            </section>

            <aside class="space-y-6 lg:sticky lg:top-28 lg:self-start" aria-label="ملخص الحساب">
                <section class="card p-5" aria-labelledby="account-summary-heading">
                    <h2 id="account-summary-heading" class="flex items-center gap-2 text-lg font-extrabold text-brand-ink">
                        <span class="grid h-9 w-9 place-items-center rounded-xl bg-brand-teal/15 text-brand-ink">
                            <x-icon name="chart" class="h-5 w-5" />
                        </span>
                        ملخص الحساب
                    </h2>

                    <dl class="mt-4 space-y-4 text-sm">
                        <div class="flex items-center justify-between gap-3 rounded-xl bg-surface-muted px-3 py-2.5">
                            <dt class="flex items-center gap-2 text-slate-600">
                                <x-icon name="orders" class="h-4 w-4 text-brand-teal" />
                                عدد الطلبات
                            </dt>
                            <dd class="font-extrabold text-brand-ink">{{ $ordersCount }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3 rounded-xl bg-surface-muted px-3 py-2.5">
                            <dt class="flex items-center gap-2 text-slate-600">
                                <x-icon name="calendar" class="h-4 w-4 text-brand-teal" />
                                تاريخ التسجيل
                            </dt>
                            <dd class="font-bold text-brand-ink">
                                <time datetime="{{ $user->created_at->toIso8601String() }}" dir="ltr">{{ $user->created_at->format('Y-m-d') }}</time>
                            </dd>
                        </div>
                    </dl>

                    <p class="mt-5 flex items-start gap-2 rounded-xl bg-surface-muted px-4 py-3 text-xs leading-6 text-slate-600">
                        <x-icon name="info" class="mt-0.5 h-4 w-4 shrink-0 text-brand-teal" />
                        تُستخدم بياناتك في الطلبات الجديدة فقط بعد حفظها. الطلبات السابقة تحتفظ ببياناتها المحفوظة وقت الإنشاء.
                    </p>
                </section>
            </aside>
        </div>
    </div>
@endsection
