@props([
    'action',
    'method' => 'DELETE',
    'title' => 'تأكيد الحذف',
    'message' => 'هل أنت متأكد؟ لا يمكن التراجع عن هذا الإجراء.',
    'label' => 'حذف',
    'icon' => 'trash',
])

@php
    $modalId = 'confirm-'.\Illuminate\Support\Str::uuid();
@endphp

<button
    type="button"
    class="table-action text-danger hover:bg-danger/5 hover:underline"
    data-modal-open="{{ $modalId }}"
>
    @if ($icon)
        <x-icon :name="$icon" class="h-4 w-4" />
    @endif
    {{ $label }}
</button>

<x-modal :id="$modalId" :title="$title">
    <p class="text-sm leading-7 text-slate-600">{{ $message }}</p>

    <div class="mt-5 flex flex-wrap gap-3">
        <form method="POST" action="{{ $action }}">
            @csrf
            @method($method)
            <button type="submit" class="btn bg-danger text-white hover:brightness-95">
                <x-icon name="check" class="h-4 w-4" />
                تأكيد
            </button>
        </form>

        <button type="button" class="btn btn-outline" data-modal-close>
            <x-icon name="close" class="h-4 w-4" />
            إلغاء
        </button>
    </div>
</x-modal>
