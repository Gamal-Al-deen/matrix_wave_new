<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cart Quantity Safety Limit
    |--------------------------------------------------------------------------
    |
    | The documents require a positive integer without a stock constraint.
    | This upper bound only prevents integer overflow in totals; it is not a
    | business limit.
    |
    */

    'max_quantity' => (int) env('CART_MAX_QUANTITY', 1000000),

];
