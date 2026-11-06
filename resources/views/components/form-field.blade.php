@props([
    'label',
    'name',
    'hint' => null,
    'required' => false,
])

<div class="space-y-1.5">
    <label for="{{ $name }}" class="block text-sm font-bold text-ink">
        {{ $label }}
        @if ($required)
            <span class="text-danger" aria-hidden="true">*</span>
        @endif
    </label>

    {{ $slot }}

    @if ($hint)
        <p id="{{ $name }}-hint" class="text-xs leading-6 text-slate-500">{{ $hint }}</p>
    @endif

    @error($name)
        <p id="{{ $name }}-error" class="text-xs font-bold text-danger">{{ $message }}</p>
    @enderror
</div>
