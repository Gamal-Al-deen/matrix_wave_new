<?php

namespace App\Http\Requests\Admin;

use App\Support\ImageRules;
use Illuminate\Foundation\Http\FormRequest;

class ProductImagesRequest extends FormRequest
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
            'images' => ['required', 'array', 'min:1', 'max:'.(int) config('media.max_product_images', 10)],
            'images.*' => ImageRules::make(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'images' => 'الصور',
            'images.*' => 'الصورة',
        ];
    }
}
