<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ArabicLocalizationTest extends TestCase
{
    public function test_application_locale_is_arabic(): void
    {
        $this->assertSame('ar', app()->getLocale());
    }

    public function test_validation_messages_are_arabic(): void
    {
        $validator = Validator::make([], [
            'name' => ['required'],
        ]);

        $this->assertSame('حقل الاسم مطلوب.', $validator->errors()->first('name'));
    }

    public function test_pagination_messages_are_arabic(): void
    {
        $this->assertStringContainsString('السابق', __('pagination.previous'));
        $this->assertStringContainsString('التالي', __('pagination.next'));
    }
}
