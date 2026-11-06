<?php

namespace App\Support;

/**
 * Shared validation rules for uploaded images.
 */
final class ImageRules
{
    /**
     * @return list<string>
     */
    public static function make(bool $required = true): array
    {
        $rules = [
            $required ? 'required' : 'nullable',
            'file',
            'mimetypes:'.implode(',', config('media.allowed_mimes', [])),
            'mimes:'.implode(',', config('media.allowed_extensions', [])),
            'max:'.(int) config('media.max_kilobytes', 5120),
        ];

        $maxWidth = (int) config('media.max_width', 4000);
        $maxHeight = (int) config('media.max_height', 4000);

        if ($maxWidth > 0 && $maxHeight > 0) {
            $rules[] = "dimensions:max_width={$maxWidth},max_height={$maxHeight}";
        }

        return $rules;
    }
}
