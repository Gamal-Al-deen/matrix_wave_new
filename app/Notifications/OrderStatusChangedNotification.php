<?php

namespace App\Notifications;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Notifications\Notification;

/**
 * Informs the customer that an administrator moved their order to a new status.
 */
class OrderStatusChangedNotification extends Notification
{
    public function __construct(
        private readonly Order $order,
        private readonly OrderStatus $status,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, string>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'تحديث حالة الطلب',
            'body' => 'تم تحديث حالة طلبك إلى «'.$this->status->label().'».',
            'url' => route('orders.show', $this->order),
            'category' => 'order',
            'order_number' => (string) $this->order->order_number,
        ];
    }
}
