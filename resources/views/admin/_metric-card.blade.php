@props([
    'tone' => 'teal',
    'icon' => 'chart',
])

@php
    $toneClasses = [
        'teal' => [
            'border' => 'border-t-brand-teal',
            'icon' => 'bg-brand-teal/15 text-brand-ink',
        ],
        'orange' => [
            'border' => 'border-t-brand-orange',
            'icon' => 'bg-brand-orange/15 text-brand-ink',
        ],
        'info' => [
            'border' => 'border-t-info',
            'icon' => 'bg-info/10 text-info',
        ],
        'success' => [
            'border' => 'border-t-success',
            'icon' => 'bg-success/10 text-success',
        ],
        'warning' => [
            'border' => 'border-t-warning',
            'icon' => 'bg-warning/10 text-warning',
        ],
    ];
    $classes = $toneClasses[$tone] ?? $toneClasses['teal'];
    $isMoney = isset($amounts);
@endphp

<article class="card card-hover border-t-4 p-5 {{ $classes['border'] }}">
    <div class="flex items-start justify-between gap-3">
        <p class="text-sm font-bold text-slate-600">{{ $label }}</p>
        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl {{ $classes['icon'] }}">
            <x-icon :name="$icon" class="h-5 w-5" />
        </span>
    </div>

    @if ($isMoney)
        <div class="mt-3 space-y-1.5">
            @forelse ($amounts as $currency => $amount)
                <p class="flex flex-wrap items-baseline gap-1.5">
                    <span class="text-2xl font-extrabold text-brand-ink" dir="ltr">{{ $amount }}</span>
                    <span class="text-xs font-bold text-slate-500" dir="ltr">{{ $currency }}</span>
                </p>
            @empty
                <p class="text-2xl font-extrabold text-brand-ink" dir="ltr">0.00</p>
            @endforelse
        </div>
    @else
        <p class="mt-3 text-3xl font-extrabold text-brand-ink">{{ number_format($count) }}</p>
    @endif

    <p class="mt-3 text-xs leading-6 text-slate-500">{{ $description }}</p>
</article>
