<?php

namespace Tests\Feature;

use App\Rules\YemeniPhone;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class YemeniPhoneValidationTest extends TestCase
{
    #[DataProvider('validPhones')]
    public function test_accepts_each_supported_yemeni_mobile_prefix(string $phone): void
    {
        $validator = Validator::make(
            ['phone' => $phone],
            ['phone' => ['required', 'string', new YemeniPhone]],
        );

        $this->assertFalse($validator->fails());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function validPhones(): array
    {
        return [
            '70 prefix' => ['701234567'],
            '71 prefix' => ['711234567'],
            '73 prefix' => ['731234567'],
            '77 prefix' => ['771234567'],
            '78 prefix' => ['781234567'],
        ];
    }

    #[DataProvider('invalidPhones')]
    public function test_rejects_invalid_yemeni_phone_numbers(string $phone): void
    {
        $validator = Validator::make(
            ['phone' => $phone],
            ['phone' => ['required', 'string', new YemeniPhone]],
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('phone', $validator->errors()->toArray());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidPhones(): array
    {
        return [
            '76 prefix' => ['761234567'],
            '79 prefix' => ['791234567'],
            '72 prefix' => ['721234567'],
            'fewer than nine digits' => ['77123456'],
            'more than nine digits' => ['7712345678'],
            'country code' => ['+967771234567'],
            'non-digit character' => ['77123A567'],
            'empty value' => [''],
        ];
    }

    public function test_rule_returns_the_arabic_validation_message(): void
    {
        $validator = Validator::make(
            ['phone' => '761234567'],
            ['phone' => [new YemeniPhone]],
        );

        $this->assertSame(
            'يرجى إدخال رقم هاتف يمني صحيح مكوّن من 9 أرقام ويبدأ بأحد الأرقام التالية: 70 أو 71 أو 73 أو 77 أو 78.',
            $validator->errors()->first('phone'),
        );
    }

    #[DataProvider('arabicDigits')]
    public function test_normalizes_arabic_digits_to_ascii_local_digits(string $input, string $expected): void
    {
        $this->assertSame($expected, YemeniPhone::normalize($input));

        $validator = Validator::make(
            ['phone' => YemeniPhone::normalize($input)],
            ['phone' => ['required', 'string', new YemeniPhone]],
        );

        $this->assertFalse($validator->fails());
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function arabicDigits(): array
    {
        return [
            'arabic-indic digits' => ['٧٧١٢٣٤٥٦٧', '771234567'],
            'eastern arabic digits' => ['۷۷۱۲۳۴۵۶۷', '771234567'],
            'outer whitespace' => [' 781234567 ', '781234567'],
        ];
    }
}
