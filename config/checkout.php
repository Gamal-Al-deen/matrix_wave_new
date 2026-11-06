<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Checkout Safety Limits
    |--------------------------------------------------------------------------
    |
    | These values mirror the database column limits or provide conservative
    | input bounds. They are not business fees or stock rules.
    |
    */

    'address_max' => 1000,

    'notes_max' => 2000,

    'token_length' => 40,

];
