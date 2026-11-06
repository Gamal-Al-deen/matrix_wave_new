@props([
    'title',
    'description' => null,
    'actionLabel' => null,
    'actionUrl' => null,
    'actionIcon' => null,
])

<div class="card flex flex-col items-center justify-center px-6 py-12 text-center">
    <div class="grid h-14 w-14 place-items-center rounded-2xl bg-brand-teal/15 text-brand-ink" aria-hidden="true">
        <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
            <path d="M4 7.5 12 3l8 4.5v9L12 21l-8-4.5z"></path>
            <path d="M4 7.5 12 12l8-4.5M12 12v9"></path>
        </svg>
    </div>

    <h2 class="mt-4 text-lg font-extrabold text-brand-ink">{{ $title }}</h2>

    @if ($description)
        <p class="mt-2 max-w-lg text-sm leading-7 text-slate-600">{{ $description }}</p>
    @endif

    @if ($actionLabel && $actionUrl)
        <a href="{{ $actionUrl }}" class="btn btn-primary mt-6">
            @if ($actionIcon)
                <x-icon :name="$actionIcon" class="h-4 w-4" />
            @endif
            {{ $actionLabel }}
        </a>
    @endif
</div>
