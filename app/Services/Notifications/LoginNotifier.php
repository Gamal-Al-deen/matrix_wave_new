<?php

namespace App\Services\Notifications;

use App\Models\User;
use App\Notifications\LoginNotification;
use Illuminate\Auth\Events\Login;

/**
 * Sends one in-app confirmation the first time a customer signs in during a
 * session, while skipping administrators and the registration auto-login.
 */
class LoginNotifier
{
    public function handle(Login $event): void
    {
        $user = $event->user;

        if (! $user instanceof User || $user->isAdmin()) {
            return;
        }

        if (request()->hasSession() && request()->session()->pull('auth.login_notified', false)) {
            return;
        }

        $user->notify(new LoginNotification);
    }
}
