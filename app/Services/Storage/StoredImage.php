<?php

namespace App\Services\Storage;

/**
 * Immutable metadata for a stored image object.
 */
final readonly class StoredImage
{
    public function __construct(
        public string $path,
        public string $publicUrl,
        public string $originalName,
        public string $mimeType,
        public int $sizeBytes,
    ) {}

    /**
     * @return array<string, string|int>
     */
    public function toArray(): array
    {
        return [
            'storage_path' => $this->path,
            'public_url' => $this->publicUrl,
            'original_name' => $this->originalName,
            'mime_type' => $this->mimeType,
            'size_bytes' => $this->sizeBytes,
        ];
    }
}
