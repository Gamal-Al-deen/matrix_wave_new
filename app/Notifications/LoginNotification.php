<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * One lightweight confirmation per successful customer login session.
 */
class LoginNotification extends Notification
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
            'title' => 'تسجيل دخول جديد',
            'body' => 'تم تسجيل الدخول إلى حسابك بنجاح. إذا لم تكن أنت، فبادر بتغيير كلمة المرور.',
            'url' => route('account'),
            'category' => 'account',
        ];
    }
}
