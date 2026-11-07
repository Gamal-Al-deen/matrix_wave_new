@props([
    'aboveBottomNav' => false,
])

@php
    $toasts = [];

    $pushToast = function (?string $type, ?string $message) use (&$toasts): void {
        if (! is_string($message) || trim($message) === '') {
            return;
        }

        $toasts[] = [
            'type' => in_array($type, ['success', 'error', 'warning', 'info'], true) ? $type : 'info',
            'message' => trim($message),
        ];
    };

    $pushToast('success', session('status'));
    $pushToast('success', session('success'));
    $pushToast('error', session('error'));
    $pushToast('warning', session('warning'));
    $pushToast('info', session('info'));

    $seenToasts = [];
    $toasts = array_values(array_filter($toasts, function (array $toast) use (&$seenToasts): bool {
        $key = $toast['type'].'|'.$toast['message'];

        if (isset($seenToasts[$key])) {
            return false;
        }

        $seenToasts[$key] = true;

        return true;
    }));

    $validationErrors = $errors->any() ? $errors->all() : [];

    $toastIcons = [
        'success' => 'check',
        'error' => 'ban',
        'warning' => 'warning',
        'info' => 'info',
    ];

    $toastDurations = [
        'success' => 4000,
        'info' => 4000,
        'warning' => 6000,
        'error' => 6000,
    ];
@endphp

<div
    @class([
        'toast-region',
        'toast-region--above-nav' => $aboveBottomNav,
    ])
    data-toast-region
    dir="rtl"
>
    <div class="toast-stack" data-toast-stack aria-live="polite" aria-atomic="false">
        @foreach ($toasts as $toast)
            <div
                class="toast toast--{{ $toast['type'] }}"
                data-toast
                data-type="{{ $toast['type'] }}"
                data-duration="{{ $toastDurations[$toast['type']] ?? 4000 }}"
                role="{{ $toast['type'] === 'error' ? 'alert' : 'status' }}"
            >
                <span class="toast__icon" aria-hidden="true">
                    <x-icon :name="$toastIcons[$toast['type']] ?? 'info'" class="h-5 w-5" />
                </span>

                <p class="toast__message">{{ $toast['message'] }}</p>

                <button type="button" class="toast__close" data-toast-close aria-label="إغلاق الإشعار">
                    <x-icon name="close" class="h-4 w-4" />
                </button>
            </div>
        @endforeach

        @if ($validationErrors !== [])
            <div
                class="toast toast--error"
                data-toast
                data-type="error"
                data-duration="8000"
                role="alert"
            >
                <span class="toast__icon" aria-hidden="true">
                    <x-icon name="ban" class="h-5 w-5" />
                </span>

                <div class="toast__message toast__message--list">
                    <ul>
                        @foreach ($validationErrors as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>

                <button type="button" class="toast__close" data-toast-close aria-label="إغلاق الإشعار">
                    <x-icon name="close" class="h-4 w-4" />
                </button>
            </div>
        @endif
    </div>

    <template data-toast-template>
        <div class="toast" data-toast role="status">
            <span class="toast__icon" aria-hidden="true"></span>
            <p class="toast__message"></p>
            <button type="button" class="toast__close" data-toast-close aria-label="إغلاق الإشعار">
                <x-icon name="close" class="h-4 w-4" />
            </button>
        </div>
    </template>
</div>
