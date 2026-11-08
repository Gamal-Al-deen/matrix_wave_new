<?php

namespace Tests\Feature\Admin;

use App\Http\Controllers\Admin\ProductImageController;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductWebPUploadTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A valid 2x2 WebP image encoded at quality 80.
     */
    private const WEBP_IMAGE = 'UklGRiQAAABXRUJQVlA4IBgAAAAwAQCdASoCAAIAAUAmJaQAA3AA/v02aAA=';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('supabase');
    }

    public function test_administrators_can_upload_a_webp_product_image(): void
    {
        $product = $this->makeProduct();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.products.images.store', $product), [
                'images' => [$this->webp('optimized.webp')],
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $image = $product->images()->sole();

        $this->assertSame('image/webp', $image->mime_type);
        $this->assertSame('optimized.webp', $image->original_name);
        $this->assertStringEndsWith('.webp', $image->storage_path);
        Storage::disk('supabase')->assertExists($image->storage_path);
    }

    public function test_administrators_can_replace_an_image_with_a_webp_file(): void
    {
        $product = $this->makeProduct();
        $image = $this->attachImage($product);
        $oldPath = $image->storage_path;

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.products.images.replace', [$product, $image]), [
                'image' => $this->webp('replacement.webp'),
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $image->refresh();

        $this->assertSame('image/webp', $image->mime_type);
        $this->assertSame('replacement.webp', $image->original_name);
        $this->assertStringEndsWith('.webp', $image->storage_path);
        $this->assertNotSame($oldPath, $image->storage_path);
        Storage::disk('supabase')->assertMissing($oldPath);
        Storage::disk('supabase')->assertExists($image->storage_path);
    }

    public function test_administrators_can_upload_a_webp_draft_image(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->postJson(route('admin.products.images.draft'), [
                'image' => $this->webp('draft.webp'),
            ]);

        $response->assertOk()->assertJson(['success' => true]);

        $key = $response->json('key');
        $draft = session(ProductImageController::DRAFT_SESSION_KEY)[$key];

        $this->assertSame('image/webp', $draft['mime_type']);
        $this->assertSame('draft.webp', $draft['original_name']);
        $this->assertStringEndsWith('.webp', $draft['storage_path']);
        Storage::disk('supabase')->assertExists($draft['storage_path']);
    }

    public function test_a_webp_file_with_non_image_content_is_rejected(): void
    {
        $product = $this->makeProduct();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.products.images.store', $product), [
                'images' => [UploadedFile::fake()->createWithContent('fake.webp', 'not an image')],
            ])
            ->assertSessionHasErrors('images.0');

        $this->assertDatabaseCount('product_images', 0);
    }

    public function test_product_forms_enable_client_side_optimization_hooks(): void
    {
        $product = $this->makeProduct([
            'name' => 'منتج للتحقق',
            'slug' => 'webp-check-product',
        ]);
        $this->attachImage($product);

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.products.create'))
            ->assertOk()
            ->assertSee('data-draft-input', false)
            ->assertSee('data-upload-url', false);

        $this->actingAs($admin)
            ->get(route('admin.products.edit', $product))
            ->assertOk()
            ->assertSee('data-product-image-optimize', false)
            ->assertSee('data-product-image-error', false)
            ->assertSee('data-product-image-preview="#product-image-preview-', false);
    }

    public function test_non_product_image_forms_do_not_enable_product_optimization(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.settings.edit'))
            ->assertOk()
            ->assertDontSee('data-product-image-optimize', false);

        $this->actingAs($admin)
            ->get(route('admin.banners.create'))
            ->assertOk()
            ->assertDontSee('data-product-image-optimize', false);

        $this->actingAs($admin)
            ->get(route('admin.wallets.create'))
            ->assertOk()
            ->assertDontSee('data-product-image-optimize', false);
    }

    private function webp(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $name,
            base64_decode(self::WEBP_IMAGE),
        );
    }

    private function attachImage(Product $product): ProductImage
    {
        $path = 'products/'.$product->id.'/'.uniqid().'.png';

        Storage::disk('supabase')->put($path, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+ip1sAAAAASUVORK5CYII='));

        return $product->images()->create([
            'storage_path' => $path,
            'public_url' => 'https://example.test/'.$path,
            'original_name' => 'photo.png',
            'mime_type' => 'image/png',
            'size_bytes' => 68,
            'sort_order' => 1,
            'alt_text' => $product->name,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeProduct(array $attributes = []): Product
    {
        $category = Category::query()->firstOrCreate(
            ['slug' => 'webp-test-category'],
            [
                'name' => 'تصنيف اختبار WebP',
                'description' => null,
                'is_active' => true,
            ],
        );

        return Product::query()->create(array_merge([
            'category_id' => $category->id,
            'name' => 'منتج اختبار WebP',
            'slug' => 'webp-test-product',
            'description' => null,
            'price' => '10.50',
            'status' => 'available',
        ], $attributes));
    }
}
