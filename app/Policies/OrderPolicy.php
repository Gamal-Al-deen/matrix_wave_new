<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    /**
     * Only administrators may browse the full order register.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Customers may only see their own orders; administrators may see all.
     */
    public function view(User $user, Order $order): bool
    {
        return $user->isAdmin() || $order->user_id === $user->id;
    }

    /**
     * Invoices follow the same ownership rule as the order itself.
     */
    public function viewInvoice(User $user, Order $order): bool
    {
        return $this->view($user, $order);
    }

    /**
     * Only administrators may change operational status.
     */
    public function updateStatus(User $user, Order $order): bool
    {
        return $user->isAdmin();
    }

    /**
     * Only administrators may change payment status.
     */
    public function updatePaymentStatus(User $user, Order $order): bool
    {
        return $user->isAdmin();
    }
}
