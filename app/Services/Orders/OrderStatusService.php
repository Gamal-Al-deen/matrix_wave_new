<?php

namespace App\Services\Orders;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use App\Notifications\OrderStatusChangedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Applies operational order-status transitions and records their audit trail.
 *
 * Payment status is intentionally not handled here: it is a separate
 * administrative value and must never be inferred from fulfillment progress.
 */
class OrderStatusService
{
    public function update(Order $order, OrderStatus $status, ?string $note, User $actor): Order
    {
        return DB::transaction(function () use ($order, $status, $note, $actor): Order {
            $lockedOrder = Order::query()
                ->whereKey($order->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $fromStatus = $lockedOrder->status;

            if (! $fromStatus->canTransitionTo($status)) {
                throw ValidationException::withMessages([
                    'status' => 'لا يمكن تغيير حالة الطلب من «'.$fromStatus->label().'» إلى «'.$status->label().'». حدّث الصفحة ثم حاول مرة أخرى.',
                ]);
            }

            $now = now();

            $lockedOrder->status = $status;

            if ($status === OrderStatus::Confirmed && $lockedOrder->confirmed_at === null) {
                $lockedOrder->confirmed_at = $now;
            }

            if ($status === OrderStatus::Completed && $lockedOrder->completed_at === null) {
                $lockedOrder->completed_at = $now;
            }

            if ($status === OrderStatus::Cancelled && $lockedOrder->cancelled_at === null) {
                $lockedOrder->cancelled_at = $now;
            }

            $lockedOrder->save();

            $lockedOrder->statusHistory()->create([
                'from_status' => $fromStatus,
                'to_status' => $status,
                'changed_by' => $actor->id,
                'note' => $this->normalizeNote($note),
            ]);

            $lockedOrder->user?->notify(new OrderStatusChangedNotification($lockedOrder, $status));

            return $lockedOrder->refresh();
        });
    }

    private function normalizeNote(?string $note): ?string
    {
        if ($note === null) {
            return null;
        }

        $note = trim($note);

        return $note === '' ? null : $note;
    }
}
