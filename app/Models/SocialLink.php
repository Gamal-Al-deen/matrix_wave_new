<?php

namespace App\Models;

use App\Enums\SocialPlatform;
use App\Support\PublicCache;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['platform', 'url', 'icon_key', 'is_active', 'sort_order'])]
class SocialLink extends Model
{
    use HasFactory, HasUuids;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'platform' => SocialPlatform::class,
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Invalidate the cached footer links whenever a link changes.
     */
    protected static function booted(): void
    {
        static::saved(static function (): void {
            PublicCache::forgetAfterCommit(PublicCache::SOCIAL_LINKS);
        });

        static::deleted(static function (): void {
            PublicCache::forgetAfterCommit(PublicCache::SOCIAL_LINKS);
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Active links in the order they are rendered in the public footer.
     *
     * @return Collection<int, self>
     */
    public static function activeLinks(): Collection
    {
        $rows = PublicCache::remember(
            PublicCache::SOCIAL_LINKS,
            PublicCache::TTL_SOCIAL_LINKS,
            static fn (): array => static::query()
                ->active()
                ->orderBy('sort_order')
                ->orderBy('created_at')
                ->get()
                ->map(static fn (self $link): array => $link->getAttributes())
                ->all(),
        );

        return is_array($rows) ? static::hydrate($rows) : new Collection;
    }
}
