<?php

namespace Tests\Feature\Storage;

use App\Services\Storage\ImageStorageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Tests\TestCase;

class ImageStorageServiceTest extends TestCase
{
    private const ONE_PIXEL_PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    private ImageStorageService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('supabase');

        config([
            'media.disk' => 'supabase',
            'services.supabase.url' => 'https://demo.supabase.co',
            'services.supabase.storage_bucket' => 'public-assets',
        ]);

        $this->service = new ImageStorageService;
    }

    public function test_it_stores_an_image_with_generated_uuid_path_and_metadata(): void
    {
        $stored = $this->service->store($this->pngUpload(), 'products/abc');

        $this->assertStringStartsWith('products/abc/', $stored->path);
        $this->assertStringEndsWith('.png', $stored->path);
        $this->assertSame('image/png', $stored->mimeType);
        $this->assertSame('photo.png', $stored->originalName);
        $this->assertGreaterThan(0, $stored->sizeBytes);
        $this->assertSame(
            'https://demo.supabase.co/storage/v1/object/public/public-assets/'.$stored->path,
            $stored->publicUrl,
        );
        Storage::disk('supabase')->assertExists($stored->path);
    }

    public function test_it_uses_the_detected_mime_type_not_the_client_extension(): void
    {
        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.'mw_'.uniqid().'.php';
        file_put_contents($path, base64_decode(self::ONE_PIXEL_PNG));

        try {
            $file = new UploadedFile($path, 'photo.php', 'application/x-php', null, true);

            $stored = $this->service->store($file, 'products/abc');
        } finally {
            @unlink($path);
        }

        $this->assertStringEndsWith('.png', $stored->path);
        $this->assertStringNotContainsString('.php', $stored->path);
    }

    public function test_replace_removes_the_previous_object_only_after_storing_the_new_one(): void
    {
        $first = $this->service->store($this->pngUpload(), 'products/abc');
        $second = $this->service->replace($this->pngUpload('second.png'), 'products/abc', $first->path);

        $this->assertNotSame($first->path, $second->path);
        Storage::disk('supabase')->assertMissing($first->path);
        Storage::disk('supabase')->assertExists($second->path);
    }

    public function test_delete_removes_the_object(): void
    {
        $stored = $this->service->store($this->pngUpload(), 'products/abc');

        $this->assertTrue($this->service->delete($stored->path));
        Storage::disk('supabase')->assertMissing($stored->path);
    }

    public function test_it_rejects_non_image_uploads(): void
    {
        $file = UploadedFile::fake()->createWithContent('document.pdf', '%PDF-1.4 fake');

        $this->expectException(InvalidArgumentException::class);

        $this->service->store($file, 'products/abc');
    }

    public function test_it_resolves_configured_folders(): void
    {
        $this->assertSame('products', $this->service->folder('product'));
        $this->assertSame('products/uuid-1', $this->service->folder('product', 'uuid-1'));
    }

    public function test_it_rejects_unknown_folder_types(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->folder('unknown');
    }

    private function pngUpload(string $name = 'photo.png'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, base64_decode(self::ONE_PIXEL_PNG));
    }
}
