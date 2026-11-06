<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $nullableString = function (mixed $value): ?string {
            if (! is_string($value)) {
                return null;
            }

            $value = trim($value);

            return $value === '' ? null : $value;
        };

        $this->merge([
            'site_name' => is_string($this->input('site_name')) ? trim($this->input('site_name')) : $this->input('site_name'),
            'description' => $nullableString($this->input('description')),
            'currency_code' => is_string($this->input('currency_code'))
                ? mb_strtoupper(trim($this->input('currency_code')))
                : $this->input('currency_code'),
            'whatsapp_number' => $nullableString($this->input('whatsapp_number')),
            'contact_phone' => $nullableString($this->input('contact_phone')),
            'contact_email' => $nullableString($this->input('contact_email')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'site_name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'currency_code' => ['required', 'string', 'max:12', 'regex:/^[A-Z]{3,12}$/'],
            'whatsapp_number' => ['nullable', 'string', 'max:30', 'regex:/\d/'],
            'contact_phone' => ['nullable', 'string', 'max:40'],
            'contact_email' => ['nullable', 'email', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'site_name' => 'اسم الموقع',
            'description' => 'وصف الموقع',
            'currency_code' => 'عملة الموقع',
            'whatsapp_number' => 'رقم واتساب',
            'contact_phone' => 'هاتف التواصل',
            'contact_email' => 'بريد التواصل',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'currency_code.regex' => 'يجب أن تتكون عملة الموقع من 3 إلى 12 حرفًا لاتينيًا كبيرًا، مثل USD أو SAR.',
            'whatsapp_number.regex' => 'يجب أن يحتوي رقم واتساب على أرقام.',
        ];
    }
}
