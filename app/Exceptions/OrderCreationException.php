<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A safe, user-facing rejection while creating an order.
 *
 * The transaction is expected to have been rolled back before the controller
 * converts this exception into an Arabic redirect message.
 */
class OrderCreationException extends RuntimeException
{
    //
}
