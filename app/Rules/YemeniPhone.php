<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates a local Yemeni mobile number.
 *
 * Numbers are stored and validated without the country code: exactly nine
 * digits beginning with one of the supported mobile prefixes.
 */
class YemeniPhone implements ValidationRule
{
    /**
     * @var array<string, string>
     */
    private const DIGIT_MAP = [
        '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
        '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
        '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
    ];

    /**
     * A legacy value that may be resubmitted unchanged.
     */
    public function __construct(private readonly ?string $preservedPhone = null) {}

    /**
     * Convert Arabic-Indic digits to ASCII and trim outer whitespace.
     */
    public static function normalize(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = strtr(trim($value), self::DIGIT_MAP);

        return $value === '' ? null : $value;
    }

    /**
     * Determine whether a normalized value is a valid local Yemeni mobile.
     */
    public static function passes(?string $phone): bool
    {
        return $phone !== null && preg_match('/^7[01378][0-9]{7}$/', $phone) === 1;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail($this->message());

            return;
        }

        $value = trim($value);

        if ($this->preservedPhone !== null && $value === $this->preservedPhone) {
            return;
        }

        if (! self::passes($value)) {
            $fail($this->message());
        }
    }

    private function message(): string
    {
        return 'يرجى إدخال رقم هاتف يمني صحيح مكوّن من 9 أرقام ويبدأ بأحد الأرقام التالية: 70 أو 71 أو 73 أو 77 أو 78.';
    }
}
