@props([
    'title',
    'description' => null,
    'actionLabel' => null,
    'actionUrl' => null,
    'actionDisabled' => false,
])

<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <h2 class="text-xl font-extrabold text-brand-ink sm:text-2xl">{{ $title }}</h2>
        @if ($description)
            <p class="mt-1 text-sm leading-7 text-slate-600">{{ $description }}</p>
        @endif
    </div>

    @if ($actionLabel)
        @if ($actionUrl && ! $actionDisabled)
            <a href="{{ $actionUrl }}" class="text-sm font-bold text-brand-ink underline decoration-brand-teal decoration-2 underline-offset-4 hover:text-brand-ink/80">
                {{ $actionLabel }}
            </a>
        @else
            <span class="inline-flex cursor-not-allowed items-center gap-1.5 text-sm font-bold text-slate-400" aria-disabled="true" title="قريبًا">
                {{ $actionLabel }}
                <span class="rounded-full bg-slate-100 px-1.5 py-0.5 text-[0.6rem] font-bold text-slate-500">قريبًا</span>
            </span>
        @endif
    @endif
</div>
