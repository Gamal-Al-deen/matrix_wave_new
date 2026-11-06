<?php

namespace App\Http\Requests\Admin;

use App\Enums\SocialPlatform;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SocialLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'platform' => is_string($this->input('platform'))
                ? mb_strtolower(trim($this->input('platform')))
                : $this->input('platform'),
            'url' => is_string($this->input('url')) ? trim($this->input('url')) : $this->input('url'),
            'sort_order' => $this->filled('sort_order') ? $this->input('sort_order') : 0,
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'platform' => ['required', Rule::enum(SocialPlatform::class)],
            'url' => ['required', 'string', 'max:1000', 'url:http,https', 'regex:~^https?://~i'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:2147483647'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'platform' => 'المنصة',
            'url' => 'رابط المنصة',
            'sort_order' => 'ترتيب العرض',
            'is_active' => 'حالة التفعيل',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'url.url' => 'أدخل رابطًا كاملًا يبدأ بـ http:// أو https://.',
            'url.regex' => 'أدخل رابطًا كاملًا يبدأ بـ http:// أو https://.',
        ];
    }
}
