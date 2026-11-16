<?php

namespace App\Models;

use App\Support\PublicCache;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'description', 'is_active'])]
class Category extends Model
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
            'is_active' => 'boolean',
        ];
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Invalidate the cached public category list whenever a category changes.
     */
    protected static function booted(): void
    {
        static::saved(static function (): void {
            PublicCache::forgetAfterCommit(PublicCache::ACTIVE_CATEGORIES);
        });

        static::deleted(static function (): void {
            PublicCache::forgetAfterCommit(PublicCache::ACTIVE_CATEGORIES);
        });
    }

    /**
     * Active categories with the number of publicly visible products.
     *
     * @return Collection<int, self>
     */
    public static function publicWithProductCounts(): Collection
    {
        $rows = PublicCache::remember(
            PublicCache::ACTIVE_CATEGORIES,
            PublicCache::TTL_ACTIVE_CATEGORIES,
            static fn (): array => static::query()
                ->active()
                ->withCount([
                    'products' => fn (Builder $query) => $query->publiclyVisible(),
                ])
                ->orderBy('name')
                ->get()
                ->map(static fn (self $category): array => $category->getAttributes())
                ->all(),
        );

        return is_array($rows) ? static::hydrate($rows) : new Collection;
    }
}
