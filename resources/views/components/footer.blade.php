@php
    $settings = \App\Models\SiteSetting::current();
    $siteName = $settings?->displayName() ?? (string) config('app.name', 'Matrix Wave');
    $description = trim((string) $settings?->description);
    $description = $description !== '' ? $description : 'وجهتك لعرض المنتجات والطلب بسهولة، بتجربة عربية واضحة ومتجاوبة.';
    $phoneHref = preg_replace('/[^\d+]/', '', (string) $settings?->contact_phone);
    // Fixed developer WhatsApp number (not editable from admin settings).
    $developerWhatsApp = 'https://wa.me/967770569805?text='.rawurlencode('مرحبًا، أنا زائر من موقع '.$siteName.' وأرغب في التواصل مع مطوّر الموقع.');
    $socialLinks = \App\Models\SocialLink::query()
        ->active()
        ->orderBy('sort_order')
        ->orderBy('created_at')
        ->get();
    $footerLinks = [
        ['label' => 'الرئيسية', 'route' => 'home'],
        ['label' => 'المنتجات', 'route' => 'products.index'],
        ['label' => 'من نحن', 'route' => 'about'],
        ['label' => 'السلة', 'route' => 'cart.index'],
    ];
@endphp

<footer class="mt-16 overflow-hidden bg-brand-ink text-white">
    <div class="h-1 bg-gradient-to-l from-brand-orange via-brand-teal to-brand-orange/70" aria-hidden="true"></div>

    <div class="container-page grid gap-9 py-10 md:grid-cols-[1.4fr_1fr_1.15fr] md:py-12">
        <div class="space-y-4">
            <a href="{{ route('home') }}" class="inline-block rounded-lg" aria-label="{{ $siteName }} — الرئيسية">
                <x-logo class="w-[165px] sm:w-[190px]" />
            </a>
            <p class="max-w-sm text-sm leading-7 text-white/70">
                {{ $description }}
            </p>
            <p class="inline-flex items-center gap-2 rounded-full border border-brand-teal/20 bg-brand-teal/10 px-3 py-1.5 text-xs font-bold text-brand-teal">
                <x-icon name="shield" class="h-4 w-4" />
                تجربة عربية واضحة وآمنة
            </p>
        </div>

        <div>
            <h2 class="text-sm font-extrabold text-brand-teal">روابط سريعة</h2>
            <ul class="mt-3 space-y-1 text-sm text-white/75">
                @foreach ($footerLinks as $link)
                    <li>
                        @if (Route::has($link['route']))
                            <a href="{{ route($link['route']) }}" class="tap-link gap-2 hover:text-white">
                                <x-icon name="chevron-left" class="h-4 w-4 text-brand-teal/80" />
                                {{ $link['label'] }}
                            </a>
                        @else
                            <span class="inline-flex cursor-not-allowed items-center gap-2 text-white/45" aria-disabled="true" title="قريبًا">
                                {{ $link['label'] }}
                                <span class="rounded-full bg-white/10 px-1.5 py-0.5 text-[0.6rem] font-bold text-white/60">قريبًا</span>
                            </span>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>

        <div>
            <h2 class="text-sm font-extrabold text-brand-teal">التواصل</h2>

            @if (filled($settings?->contact_phone) || filled($settings?->contact_email))
                <ul class="mt-3 space-y-2 text-sm text-white/75">
                    @if (filled($settings?->contact_phone))
                        <li>
                            <span class="inline-flex items-center gap-1.5 text-white/55">
                                <x-icon name="phone" class="h-4 w-4" />
                                هاتف:
                            </span>
                            <a href="tel:{{ $phoneHref }}" class="tap-link font-bold hover:text-white" dir="ltr">{{ $settings->contact_phone }}</a>
                        </li>
                    @endif

                    @if (filled($settings?->contact_email))
                        <li>
                            <span class="text-white/55">البريد:</span>
                            <a href="mailto:{{ $settings->contact_email }}" class="tap-link break-all font-bold hover:text-white" dir="ltr">{{ $settings->contact_email }}</a>
                        </li>
                    @endif
                </ul>
            @else
                @if ($socialLinks->isEmpty())
                    <p class="mt-3 text-sm leading-7 text-white/70">
                        ستُعرض بيانات التواصل هنا بعد إعدادها من لوحة الإدارة.
                    </p>
                @endif
            @endif

            @if ($socialLinks->isNotEmpty())
                <div class="mt-6">
                    <h3 class="text-xs font-extrabold text-white/70">تابعنا</h3>
                    <ul class="mt-3 flex flex-wrap gap-2">
                        @foreach ($socialLinks as $socialLink)
                            <li>
                                <a
                                    href="{{ $socialLink->url }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="grid h-11 w-11 place-items-center rounded-full border border-white/15 bg-white/5 text-white/75 transition hover:border-brand-teal/60 hover:bg-brand-teal/10 hover:text-brand-teal focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-teal"
                                    aria-label="{{ $socialLink->platform->label() }} — يفتح في نافذة جديدة"
                                    title="{{ $socialLink->platform->label() }}"
                                >
                                    <x-social-icon :platform="$socialLink->platform" class="h-4 w-4" />
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>

    <div class="border-t border-white/10">
        <div class="container-page flex flex-col gap-2 py-4 pb-[calc(5.25rem+env(safe-area-inset-bottom))] text-xs text-white/60 sm:flex-row sm:items-center sm:justify-between md:pb-4">
            <p class="break-words font-bold text-white/75">
                تم التطوير بواسطة م/جمال الدين
                <a
                    href="{{ $developerWhatsApp }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="tap-link font-bold text-brand-teal underline decoration-brand-teal/60 decoration-2 underline-offset-4 hover:text-white"
                    dir="ltr"
                    aria-label="التواصل مع مطوّر الموقع عبر واتساب — 770569805"
                >770569805</a>
            </p>
            <p class="text-white/45">صُنع بعناية لتجربة عربية أصيلة.</p>
        </div>
    </div>
</footer>
