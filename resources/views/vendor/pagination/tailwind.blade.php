@if ($paginator->hasPages())
    <nav role="navigation" aria-label="ترقيم الصفحات" class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm leading-6 text-slate-600">
            @if ($paginator->firstItem())
                عرض <span class="font-bold text-brand-ink">{{ $paginator->firstItem() }}</span>
                إلى <span class="font-bold text-brand-ink">{{ $paginator->lastItem() }}</span>
                من أصل <span class="font-bold text-brand-ink">{{ $paginator->total() }}</span> نتيجة
            @else
                لا توجد نتائج لعرضها
            @endif
        </p>

        <div class="flex flex-wrap items-center gap-1">
            @if ($paginator->onFirstPage())
                <span class="inline-flex min-h-11 min-w-11 cursor-not-allowed items-center justify-center rounded-lg border border-brand-ink/10 px-3 text-sm font-bold text-slate-400" aria-disabled="true">
                    السابق
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="الصفحة السابقة" class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-lg border border-brand-ink/10 px-3 text-sm font-bold text-brand-ink hover:border-brand-teal hover:bg-brand-teal/10">
                    السابق
                </a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="inline-flex min-h-11 min-w-11 items-center justify-center px-2 text-sm font-bold text-slate-500" aria-hidden="true">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-lg bg-brand-ink px-3 text-sm font-bold text-white" aria-current="page" aria-label="الصفحة {{ $page }}">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" aria-label="الانتقال إلى الصفحة {{ $page }}" class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-lg border border-brand-ink/10 px-3 text-sm font-bold text-brand-ink hover:border-brand-teal hover:bg-brand-teal/10">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="الصفحة التالية" class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-lg border border-brand-ink/10 px-3 text-sm font-bold text-brand-ink hover:border-brand-teal hover:bg-brand-teal/10">
                    التالي
                </a>
            @else
                <span class="inline-flex min-h-11 min-w-11 cursor-not-allowed items-center justify-center rounded-lg border border-brand-ink/10 px-3 text-sm font-bold text-slate-400" aria-disabled="true">
                    التالي
                </span>
            @endif
        </div>
    </nav>
@endif
