<?php

namespace App\Http\Requests\Admin;

use App\Enums\ProductStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => is_string($this->input('name')) ? trim($this->input('name')) : $this->input('name'),
            'slug' => is_string($this->input('slug')) && trim($this->input('slug')) !== ''
                ? Str::lower(trim($this->input('slug')))
                : null,
            'price' => is_string($this->input('price')) ? str_replace(',', '.', trim($this->input('price'))) : $this->input('price'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $product = $this->route('product');

        return [
            'category_id' => ['required', 'uuid', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:200'],
            'slug' => [
                'nullable',
                'string',
                'max:220',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('products', 'slug')->ignore($product?->id),
            ],
            'description' => ['nullable', 'string', 'max:10000'],
            'price' => ['required', 'numeric', 'min:0', 'max:9999999999.99', 'decimal:0,2'],
            'status' => ['required', Rule::enum(ProductStatus::class)],
            // Draft images are uploaded automatically before publishing and are
            // referenced here by their session keys. At least one is required
            // when creating a product; edits manage images through the gallery.
            'draft_images' => $product === null
                ? ['required', 'array', 'min:1']
                : ['nullable', 'array'],
            'draft_images.*' => ['string', 'max:64'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'draft_images' => 'صور المنتج',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'draft_images.required' => 'يجب رفع صورة واحدة على الأقل قبل نشر المنتج.',
            'draft_images.min' => 'يجب رفع صورة واحدة على الأقل قبل نشر المنتج.',
        ];
    }
}
