<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class BannerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'title' => is_string($this->input('title')) ? trim($this->input('title')) : $this->input('title'),
            'alt_text' => is_string($this->input('alt_text')) ? trim($this->input('alt_text')) : $this->input('alt_text'),
            'sort_order' => $this->filled('sort_order') ? $this->input('sort_order') : 0,
            'is_active' => $this->boolean('is_active'),
            'starts_at' => $this->filled('starts_at') ? $this->input('starts_at') : null,
            'ends_at' => $this->filled('ends_at') ? $this->input('ends_at') : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $endsAt = ['nullable', 'date'];

        if ($this->filled('starts_at')) {
            $endsAt[] = 'after:starts_at';
        }

        return [
            'title' => ['nullable', 'string', 'max:200'],
            'alt_text' => ['required', 'string', 'max:255'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:2147483647'],
            'is_active' => ['boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => $endsAt,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => 'عنوان البانر',
            'alt_text' => 'النص البديل للصورة',
            'sort_order' => 'ترتيب العرض',
            'is_active' => 'حالة التفعيل',
            'starts_at' => 'بداية النشر',
            'ends_at' => 'نهاية النشر',
        ];
    }
}
