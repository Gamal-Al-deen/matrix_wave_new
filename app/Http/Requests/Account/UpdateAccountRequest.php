<?php

namespace App\Http\Requests\Account;

use App\Rules\YemeniPhone;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAccountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Normalize user input before validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => is_string($this->input('name')) ? trim($this->input('name')) : $this->input('name'),
            'phone' => YemeniPhone::normalize($this->input('phone')),
            'default_address' => is_string($this->input('default_address')) && trim($this->input('default_address')) !== ''
                ? trim($this->input('default_address'))
                : null,
        ]);

        if ($this->has('browser_notifications_enabled')) {
            $this->merge([
                'browser_notifications_enabled' => $this->boolean('browser_notifications_enabled'),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $currentPhone = YemeniPhone::normalize($this->user()?->phone);
        $preservedPhone = is_string($currentPhone) && ! YemeniPhone::passes($currentPhone)
            ? $currentPhone
            : null;

        return [
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['nullable', 'string', new YemeniPhone($preservedPhone)],
            'default_address' => ['nullable', 'string', 'max:'.(int) config('checkout.address_max', 1000)],
            'browser_notifications_enabled' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'يرجى إدخال الاسم الكامل.',
            'name.max' => 'الاسم الكامل طويل جدًا. الحد الأقصى 150 حرفًا.',
            'default_address.max' => 'العنوان الافتراضي طويل جدًا. يرجى تقصيره.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'الاسم الكامل',
            'phone' => 'رقم الهاتف',
            'default_address' => 'العنوان الافتراضي',
        ];
    }
}
