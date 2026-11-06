<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Notifications\Notification;

/**
 * Alerts every administrator about a newly created order.
 *
 * The message is built from the order's stored snapshots so it never depends
 * on the related customer account or on who submitted the order.
 */
class NewOrderPlacedNotification extends Notification
{
    public function __construct(private readonly Order $order) {}

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
        $total = number_format((float) $this->order->total, 2).' '.$this->order->currency_code;

        return [
            'title' => 'طلب جديد',
            'body' => sprintf(
                'وصل طلب جديد رقم %s من %s بقيمة %s.',
                $this->order->order_number,
                $this->order->customer_name_snapshot,
                $total,
            ),
            'url' => route('admin.orders.show', $this->order),
            'category' => 'order',
            'order_number' => $this->order->order_number,
        ];
    }
}
