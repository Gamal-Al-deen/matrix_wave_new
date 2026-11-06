@extends('layouts.app')

@php
    $pageTitle = trim((string) $about->title) ?: 'من نحن';
    $plainBody = trim((string) $about->body);
    $metaDescription = \Illuminate\Support\Str::limit(
        preg_replace('/\s+/u', ' ', $plainBody) ?: 'تعرّف على قصة المتجر وطريقة التواصل معه.',
        155,
    );
@endphp

@section('title', $pageTitle)
@section('meta_description', $metaDescription)

@section('content')
    <div class="container-page py-7 sm:py-10">
        <x-breadcrumbs :items="[
            ['label' => 'الرئيسية', 'url' => route('home')],
            ['label' => $pageTitle],
        ]" />

        <article class="mx-auto max-w-4xl">
            <header class="relative overflow-hidden rounded-3xl bg-brand-ink px-6 py-8 text-white shadow-xl sm:px-10 sm:py-12">
                <div class="absolute inset-0 opacity-20" aria-hidden="true">
                    <div class="absolute -start-20 -top-16 h-52 w-52 rounded-full bg-brand-teal blur-3xl"></div>
                    <div class="absolute -end-16 bottom-0 h-56 w-56 rounded-full bg-brand-orange blur-3xl"></div>
                </div>

                <div class="relative">
                    <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="inline-flex items-center gap-2 rounded-full border border-brand-teal/30 bg-brand-teal/10 px-3 py-1 text-xs font-bold text-brand-teal">
                                <x-icon name="info" class="h-3.5 w-3.5" />
                                من نحن
                            </p>
                            <h1 class="mt-3 text-3xl font-extrabold leading-tight sm:text-4xl">{{ $pageTitle }}</h1>
                            <p class="mt-4 max-w-2xl text-sm leading-8 text-white/75">
                                تعرّف على هوية المتجر وقيمه وطريقة التواصل معه.
                            </p>
                        </div>

                        <x-logo class="w-[150px] shrink-0 sm:w-[180px]" />
                    </div>
                </div>
            </header>

            <div class="card mt-5 p-5 sm:p-8">
                @if ($plainBody !== '')
                    <div class="whitespace-pre-line text-base leading-8 text-slate-700">{{ $plainBody }}</div>
                @else
                    <div class="rounded-2xl border border-dashed border-brand-ink/15 bg-surface-muted px-6 py-10 text-center">
                        <span class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-brand-teal/15 text-brand-ink" aria-hidden="true">
                            <x-icon name="info" class="h-7 w-7" />
                        </span>
                        <h2 class="mt-4 text-lg font-extrabold text-brand-ink">لم يُضف محتوى بعد</h2>
                        <p class="mx-auto mt-2 max-w-lg text-sm leading-7 text-slate-600">
                            سيظهر محتوى صفحة «من نحن» هنا بعد إضافته من لوحة الإدارة.
                        </p>
                    </div>
                @endif
            </div>

            <div class="mt-5 flex flex-col gap-4 rounded-2xl border border-brand-ink/10 bg-white p-5 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-start gap-3">
                    <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-brand-teal/15 text-brand-ink">
                        <x-icon name="box" class="h-5 w-5" />
                    </span>
                    <div>
                        <h2 class="text-base font-extrabold text-brand-ink">هل أنت جاهز لتصفح المنتجات؟</h2>
                        <p class="mt-1 text-sm leading-7 text-slate-600">اكتشف ما هو متاح الآن وأضف ما يناسبك إلى السلة.</p>
                    </div>
                </div>
                <a href="{{ route('products.index') }}" class="btn btn-primary shrink-0">
                    <x-icon name="box" class="h-4 w-4" />
                    تصفح المنتجات
                </a>
            </div>
        </article>
    </div>
@endsection
