<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class WalletRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => is_string($this->input('name')) ? trim($this->input('name')) : $this->input('name'),
            'account_identifier' => is_string($this->input('account_identifier'))
                ? trim($this->input('account_identifier'))
                : $this->input('account_identifier'),
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
            'name' => ['required', 'string', 'max:120'],
            'account_identifier' => ['required', 'string', 'max:255'],
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
            'name' => 'اسم المحفظة',
            'account_identifier' => 'معرف التحويل',
            'sort_order' => 'ترتيب العرض',
            'is_active' => 'حالة التفعيل',
        ];
    }
}
