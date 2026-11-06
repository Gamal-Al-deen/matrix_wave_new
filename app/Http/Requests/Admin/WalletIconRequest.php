<?php

namespace App\Http\Requests\Admin;

use App\Support\ImageRules;
use Illuminate\Foundation\Http\FormRequest;

class WalletIconRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'icon' => ['required', ...ImageRules::make()],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'icon' => 'أيقونة المحفظة',
        ];
    }
}
