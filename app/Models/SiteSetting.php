<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

#[Fillable([
    'site_name',
    'logo_storage_path',
    'logo_public_url',
    'description',
    'currency_code',
    'whatsapp_number',
    'contact_phone',
    'contact_email',
])]
class SiteSetting extends Model
{
    public const SINGLETON_ID = 1;

    /**
     * Container key that reuses the singleton row during a single request.
     */
    private const CURRENT_CACHE_KEY = 'site_settings.current';

    public $incrementing = false;

    protected $primaryKey = 'id';

    protected $keyType = 'int';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
        ];
    }

    /**
     * Invalidate the request-level copy whenever the singleton row changes.
     */
    protected static function booted(): void
    {
        static::saved(static function (): void {
            self::forgetCurrent();
        });

        static::deleted(static function (): void {
            self::forgetCurrent();
        });
    }

    /**
     * Resolve the singleton row once per request.
     */
    public static function current(): ?self
    {
        if (app()->bound(self::CURRENT_CACHE_KEY)) {
            $cached = app(self::CURRENT_CACHE_KEY);

            return $cached instanceof self ? $cached : null;
        }

        $settings = static::query()->find(self::SINGLETON_ID);
        self::rememberCurrent($settings);

        return $settings;
    }

    /**
     * Drop the request-level copy so the next read queries the database again.
     */
    public static function forgetCurrent(): void
    {
        app()->forgetInstance(self::CURRENT_CACHE_KEY);
    }

    private static function rememberCurrent(?self $settings): void
    {
        // `false` is a sentinel for "resolved, but no row exists".
        app()->instance(self::CURRENT_CACHE_KEY, $settings ?? false);
    }

    public static function currentOrCreate(): self
    {
        $settings = self::current();

        if ($settings !== null) {
            return $settings;
        }

        $settings = new self;
        $settings->id = self::SINGLETON_ID;
        $settings->site_name = (string) config('app.name', 'Matrix Wave');
        $settings->currency_code = 'USD';
        $settings->save();

        self::rememberCurrent($settings);

        return $settings;
    }

    public function displayName(): string
    {
        $name = trim((string) $this->site_name);

        return $name !== '' ? $name : (string) config('app.name', 'Matrix Wave');
    }

    public function hasCustomLogo(): bool
    {
        return filled($this->logo_storage_path);
    }

    /**
     * Resolve the configured logo or the supplied Matrix Wave fallback.
     */
    public function logoUrl(): string
    {
        $url = trim((string) $this->logo_public_url);

        if ($url === '') {
            return asset('images/branding/matrix-wave-logo-2x.jpg');
        }

        return Str::startsWith($url, ['http://', 'https://', '//', '/'])
            ? $url
            : asset($url);
    }
}
