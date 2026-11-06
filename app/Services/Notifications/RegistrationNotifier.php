<?php

namespace App\Services\Notifications;

use App\Enums\UserRole;
use App\Models\User;
use App\Notifications\NewCustomerRegisteredNotification;
use App\Notifications\WelcomeNotification;
use Illuminate\Auth\Events\Registered;

/**
 * Creates the administrator alert and the customer welcome message once the
 * registration request has successfully created the account.
 */
class RegistrationNotifier
{
    public function handle(Registered $event): void
    {
        $customer = $event->user;

        if (! $customer instanceof User || $customer->isAdmin()) {
            return;
        }

        User::query()
            ->where('role', UserRole::Admin->value)
            ->each(fn (User $admin) => $admin->notify(new NewCustomerRegisteredNotification($customer)));

        $customer->notify(new WelcomeNotification);

        if (request()->hasSession()) {
            // The registration flow signs the new customer in immediately.
            // Suppress the separate login confirmation for that first session.
            request()->session()->put('auth.login_notified', true);
        }
    }
}
