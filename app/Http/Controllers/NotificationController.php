<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Reads and updates only the authenticated user's own notifications.
 */
class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        // Opening the notifications page counts as reading everything that is
        // already waiting, so the unread badge disappears immediately.
        $user->unreadNotifications()->update(['read_at' => now()]);

        $notifications = $user->notifications()
            ->latest('created_at')
            ->paginate(20);

        return view('notifications.index', [
            'notifications' => $notifications,
            'unreadCount' => $user->unreadNotifications()->count(),
        ]);
    }

    public function read(Request $request, string $notification): RedirectResponse
    {
        $record = $request->user()
            ->notifications()
            ->whereKey($notification)
            ->firstOrFail();

        if ($record->read_at === null) {
            $record->markAsRead();
        }

        return redirect()->to($this->safeRedirectUrl((array) $record->data));
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()
            ->unreadNotifications()
            ->update(['read_at' => now()]);

        return redirect()
            ->route('notifications.index')
            ->with('status', 'تم تعليم جميع الإشعارات كمقروءة.');
    }

    /**
     * Lightweight polling endpoint used to refresh badges and, when enabled,
     * show browser pop-ups for notifications that arrived after page load.
     */
    public function poll(Request $request): JsonResponse
    {
        $user = $request->user();

        $notifications = $user->unreadNotifications()
            ->latest('created_at')
            ->limit(5)
            ->get()
            ->map(fn ($notification): array => [
                'id' => $notification->id,
                'title' => (string) data_get($notification->data, 'title', 'إشعار جديد'),
                'body' => (string) data_get($notification->data, 'body', ''),
                'url' => $this->safeRedirectUrl((array) $notification->data),
                'category' => (string) data_get($notification->data, 'category', 'general'),
            ])
            ->values();

        return response()->json([
            'unread_count' => $user->unreadNotifications()->count(),
            'pending_orders' => $user->isAdmin()
                ? Order::query()->where('status', OrderStatus::Pending->value)->count()
                : null,
            'notifications' => $notifications,
        ]);
    }

    /**
     * Never follow a notification to another host.
     *
     * @param  array<string, mixed>  $data
     */
    private function safeRedirectUrl(array $data): string
    {
        $url = $data['url'] ?? null;

        if (! is_string($url) || $url === '') {
            return route('notifications.index');
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);
        $host = parse_url($url, PHP_URL_HOST);

        if (! is_string($scheme) || ! in_array(strtolower($scheme), ['http', 'https'], true)) {
            return route('notifications.index');
        }

        if (! is_string($host) || strcasecmp($host, request()->getHost()) !== 0) {
            return route('notifications.index');
        }

        return $url;
    }
}
