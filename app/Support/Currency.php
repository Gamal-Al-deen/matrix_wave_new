<?php

namespace App\Support;

use App\Models\SiteSetting;

final class Currency
{
    /**
     * The single configured website currency code.
     */
    public static function code(): string
    {
        $code = SiteSetting::current()?->currency_code;

        return is_string($code) && $code !== '' ? $code : 'USD';
    }
}
