@props([
    'id',
    'title' => null,
])

<dialog
    id="{{ $id }}"
    class="m-auto w-[min(92vw,32rem)] rounded-2xl border-0 bg-white p-0 text-ink shadow-2xl"
    @if ($title) aria-labelledby="{{ $id }}-title" @endif
>
    <div class="flex items-start justify-between gap-4 border-b border-brand-ink/10 px-5 py-4">
        @if ($title)
            <h2 id="{{ $id }}-title" class="text-lg font-extrabold text-brand-ink">{{ $title }}</h2>
        @else
            <span></span>
        @endif

        <button type="button" class="inline-grid h-11 w-11 shrink-0 place-items-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-brand-ink" data-modal-close aria-label="إغلاق">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M6 6l12 12M18 6 6 18"></path>
            </svg>
        </button>
    </div>

    <div class="px-5 py-5">
        {{ $slot }}
    </div>
</dialog>
