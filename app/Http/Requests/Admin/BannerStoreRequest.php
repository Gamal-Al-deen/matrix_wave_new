<?php

namespace App\Http\Requests\Admin;

use App\Support\ImageRules;

class BannerStoreRequest extends BannerRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'image' => ['required', ...ImageRules::make()],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            ...parent::attributes(),
            'image' => 'صورة البانر',
        ];
    }
}
