<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Generates URL-friendly unique slugs, including for Arabic names that
 * transliterate to an empty ASCII string.
 */
final class UniqueSlug
{
    /**
     * @param  class-string<Model>  $modelClass
     */
    public static function make(
        string $modelClass,
        string $name,
        ?string $ignoreId = null,
        string $fallbackPrefix = 'item',
    ): string {
        $base = Str::slug($name);

        if ($base === '') {
            $base = $fallbackPrefix.'-'.Str::lower(Str::random(8));
        }

        $slug = $base;
        $suffix = 2;

        while (
            $modelClass::query()
                ->where('slug', $slug)
                ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
                ->exists()
        ) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
