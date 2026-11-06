@props([
    'items' => [],
])

@if (count($items))
    <nav aria-label="مسار التنقل" class="mb-4">
        <ol class="flex flex-wrap items-center gap-1.5 text-xs text-slate-500">
            @foreach ($items as $item)
                <li class="flex items-center gap-1.5">
                    @if (! $loop->first)
                        <span aria-hidden="true" class="text-slate-300">/</span>
                    @endif

                    @if (! empty($item['url']) && ! $loop->last)
                        <a href="{{ $item['url'] }}" class="tap-link font-bold hover:text-brand-ink">{{ $item['label'] }}</a>
                    @else
                        <span @if ($loop->last) aria-current="page" class="font-bold text-brand-ink" @endif>{{ $item['label'] }}</span>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endif
