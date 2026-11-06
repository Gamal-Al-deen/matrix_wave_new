<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Notification;

/**
 * Tells administrators that a new customer account was created.
 */
class NewCustomerRegisteredNotification extends Notification
{
    public function __construct(private readonly User $customer) {}

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
            'title' => 'تسجيل عميل جديد',
            'body' => sprintf('انضم %s إلى المتجر بحساب جديد.', $this->customer->name),
            'url' => route('admin.customers.show', $this->customer),
            'category' => 'customer',
        ];
    }
}
