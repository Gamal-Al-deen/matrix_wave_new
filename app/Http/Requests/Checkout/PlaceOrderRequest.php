<?php

namespace App\Http\Requests\Checkout;

use App\Rules\YemeniPhone;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlaceOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Normalize the optional default-address checkbox before validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'phone' => YemeniPhone::normalize($this->input('phone')),
            'save_default_address' => $this->boolean('save_default_address'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['required', 'string', new YemeniPhone],
            'email' => ['nullable', 'email', 'max:255'],
            'delivery_address' => ['required', 'string', 'max:'.(int) config('checkout.address_max', 1000)],
            'save_default_address' => ['sometimes', 'boolean'],
            'wallet_id' => [
                'nullable',
                'uuid',
                Rule::exists('wallets', 'id')->where('is_active', true),
            ],
            'notes' => ['nullable', 'string', 'max:'.(int) config('checkout.notes_max', 2000)],
            'checkout_token' => [
                'required',
                'string',
                'size:'.(int) config('checkout.token_length', 40),
                function (string $attribute, mixed $value, Closure $fail): void {
                    $expected = $this->session()->get('checkout.token');

                    if (! is_string($expected) || ! hash_equals($expected, (string) $value)) {
                        $fail('انتهت صلاحية نموذج الطلب. حدّث الصفحة ثم أعد المحاولة.');
                    }
                },
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'يرجى إدخال الاسم الكامل.',
            'phone.required' => 'يرجى إدخال رقم الهاتف.',
            'email.email' => 'أدخل بريدًا إلكترونيًا صحيحًا أو اتركه فارغًا.',
            'delivery_address.required' => 'يرجى إدخال عنوان التوصيل.',
            'wallet_id.uuid' => 'المحفظة المختارة غير صالحة.',
            'wallet_id.exists' => 'المحفظة المختارة غير متاحة حاليًا.',
            'notes.max' => 'الملاحظات طويلة جدًا. يرجى تقصيرها.',
            'checkout_token.required' => 'انتهت صلاحية نموذج الطلب. حدّث الصفحة ثم أعد المحاولة.',
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
            'email' => 'البريد الإلكتروني',
            'delivery_address' => 'عنوان التوصيل',
            'save_default_address' => 'حفظ العنوان الافتراضي',
            'wallet_id' => 'المحفظة',
            'notes' => 'الملاحظات',
            'checkout_token' => 'رمز الطلب',
        ];
    }
}
