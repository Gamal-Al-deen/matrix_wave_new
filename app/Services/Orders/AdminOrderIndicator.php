<?php

namespace App\Services\Orders;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Tracks how many orders arrived since the administrator last opened the
 * order-management page.
 *
 * The baseline timestamp is kept in the administrator's session only, so the
 * count is specific to the current session/device. No database column,
 * migration or schema change is involved.
 */
final class AdminOrderIndicator
{
    private const SESSION_KEY = 'admin.orders.viewed_at';

    /**
     * Record "now" as the moment the administrator reviewed the orders list.
     */
    public function markViewed(Request $request): void
    {
        if (! $request->hasSession()) {
            return;
        }

        $request->session()->put(self::SESSION_KEY, now()->getTimestamp());
    }

    /**
     * Count orders created after the last recorded review moment.
     *
     * Returns zero until a baseline exists for the current session.
     */
    public function newCount(Request $request): int
    {
        if (! $request->hasSession()) {
            return 0;
        }

        $viewedAt = $request->session()->get(self::SESSION_KEY);

        if (! is_int($viewedAt) && ! (is_string($viewedAt) && ctype_digit($viewedAt))) {
            return 0;
        }

        return Order::query()
            ->where('created_at', '>', Carbon::createFromTimestamp((int) $viewedAt))
            ->count();
    }
}
