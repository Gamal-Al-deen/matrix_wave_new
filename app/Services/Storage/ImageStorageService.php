<?php

namespace App\Services\Storage;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * Stores and removes public images on the configured Supabase Storage disk.
 *
 * The service never deletes an old object before its replacement has been
 * written successfully, so a failed upload leaves the previous image usable.
 */
class ImageStorageService
{
    /**
     * Store an uploaded image and return its persisted metadata.
     */
    public function store(UploadedFile $file, string $folder): StoredImage
    {
        $folder = $this->normalizeFolder($folder);
        $mimeType = (string) $file->getMimeType();
        $extension = $this->extensionFor($mimeType);
        $path = $folder.'/'.Str::uuid()->toString().'.'.$extension;

        $stream = fopen($file->getRealPath(), 'rb');

        if ($stream === false) {
            throw new RuntimeException('تعذر قراءة الملف المرفوع.');
        }

        try {
            $written = $this->disk()->writeStream($path, $stream);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        if (! $written) {
            throw new RuntimeException('تعذر رفع الصورة إلى التخزين.');
        }

        return new StoredImage(
            path: $path,
            publicUrl: $this->publicUrl($path),
            originalName: (string) $file->getClientOriginalName(),
            mimeType: $mimeType,
            sizeBytes: (int) $file->getSize(),
        );
    }

    /**
     * Delete an object. Failures are logged without exposing credentials.
     */
    public function delete(?string $path): bool
    {
        if ($path === null || trim($path) === '') {
            return true;
        }

        try {
            return $this->disk()->delete($path);
        } catch (Throwable $exception) {
            Log::error('Supabase storage deletion failed.', [
                'path' => $path,
                'exception' => $exception::class,
            ]);

            return false;
        }
    }

    /**
     * Build the public URL for an object inside the public bucket.
     */
    public function publicUrl(string $path): string
    {
        $base = rtrim((string) config('services.supabase.url'), '/');
        $bucket = trim((string) config('services.supabase.storage_bucket', 'public-assets'), '/');

        if ($base === '') {
            return $this->disk()->url($path);
        }

        return $base.'/storage/v1/object/public/'.$bucket.'/'.ltrim($path, '/');
    }

    /**
     * Resolve a configured storage folder, optionally scoped to an entity.
     */
    public function folder(string $type, ?string $entityId = null): string
    {
        $prefix = config('media.folders.'.$type);

        if (! is_string($prefix) || $prefix === '') {
            throw new InvalidArgumentException("Unknown media folder type [{$type}].");
        }

        return $entityId === null ? $prefix : $prefix.'/'.$entityId;
    }

    public function diskName(): string
    {
        return (string) config('media.disk', 'supabase');
    }

    protected function disk(): Filesystem
    {
        return Storage::disk($this->diskName());
    }

    protected function normalizeFolder(string $folder): string
    {
        $folder = trim($folder, '/');

        if ($folder === '' || str_contains($folder, '..')) {
            throw new InvalidArgumentException('Invalid storage folder.');
        }

        return $folder;
    }

    /**
     * Derive a safe extension from the detected MIME type, never from the
     * client-provided file name.
     */
    protected function extensionFor(string $mimeType): string
    {
        $map = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];

        if (! isset($map[$mimeType]) || ! in_array($mimeType, config('media.allowed_mimes', []), true)) {
            throw new InvalidArgumentException('Unsupported image type.');
        }

        return $map[$mimeType];
    }
}
