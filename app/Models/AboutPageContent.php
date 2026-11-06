<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['title', 'body', 'updated_by'])]
class AboutPageContent extends Model
{
    public const SINGLETON_ID = 1;

    public $incrementing = false;

    protected $table = 'about_page_content';

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

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public static function current(): ?self
    {
        return static::query()->find(self::SINGLETON_ID);
    }

    public static function currentOrCreate(): self
    {
        $content = self::current();

        if ($content !== null) {
            return $content;
        }

        $content = new self;
        $content->id = self::SINGLETON_ID;
        $content->title = 'من نحن';
        $content->body = '';
        $content->save();

        return $content;
    }
}
