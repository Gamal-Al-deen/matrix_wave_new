<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * Welcome message created once when a customer registers.
 */
class WelcomeNotification extends Notification
{
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
            'title' => 'مرحباً بك في '.config('app.name', 'Matrix Wave'),
            'body' => 'تم إنشاء حسابك بنجاح. يمكنك الآن تصفّح المنتجات وإتمام طلباتك ومتابعتها من صفحة طلباتي.',
            'url' => route('orders.index'),
            'category' => 'account',
        ];
    }
}
