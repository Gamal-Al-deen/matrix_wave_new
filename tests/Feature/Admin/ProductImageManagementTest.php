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

class ProductImageManagementTest extends TestCase
{
    use RefreshDatabase;

    private const ONE_PIXEL_PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+ip1sAAAAASUVORK5CYII=';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('supabase');
    }

    public function test_guests_are_redirected_when_uploading_images(): void
    {
        $product = $this->makeProduct();

        $this->post(route('admin.products.images.store', $product), [
            'images' => [$this->png('photo.png')],
        ])->assertRedirect(route('admin.login'));
    }

    public function test_customers_cannot_upload_images(): void
    {
        $product = $this->makeProduct();

        $this->actingAs(User::factory()->create())
            ->post(route('admin.products.images.store', $product), [
                'images' => [$this->png('photo.png')],
            ])
            ->assertForbidden();
    }

    public function test_administrators_can_upload_ordered_images(): void
    {
        $product = $this->makeProduct();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.products.images.store', $product), [
                'images' => [$this->png('first.png'), $this->png('second.png')],
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $images = $product->images()->get();

        $this->assertCount(2, $images);
        $this->assertSame([1, 2], $images->pluck('sort_order')->all());
        $this->assertSame($product->name, $images->first()->alt_text);

        foreach ($images as $image) {
            Storage::disk('supabase')->assertExists($image->storage_path);
            $this->assertNotEmpty($image->public_url);
            $this->assertSame('image/png', $image->mime_type);
        }
    }

    public function test_non_image_files_are_rejected(): void
    {
        $product = $this->makeProduct();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.products.images.store', $product), [
                'images' => [UploadedFile::fake()->createWithContent('notes.txt', 'not an image')],
            ])
            ->assertSessionHasErrors('images.0');

        $this->assertDatabaseCount('product_images', 0);
    }

    public function test_the_per_product_image_limit_is_enforced(): void
    {
        config(['media.max_product_images' => 2]);

        $product = $this->makeProduct();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.products.images.store', $product), [
                'images' => [
                    $this->png('first.png'),
                    $this->png('second.png'),
                    $this->png('third.png'),
                ],
            ])
            ->assertSessionHasErrors('images');

        $this->assertDatabaseCount('product_images', 0);
    }

    public function test_images_can_be_deleted_with_their_storage_object(): void
    {
        $product = $this->makeProduct();
        $image = $this->attachImage($product);

        $this->actingAs(User::factory()->admin()->create())
            ->delete(route('admin.products.images.destroy', [$product, $image]))
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('product_images', ['id' => $image->id]);
        Storage::disk('supabase')->assertMissing($image->storage_path);
    }

    public function test_replacing_an_image_writes_the_new_file_before_removing_the_old_one(): void
    {
        $product = $this->makeProduct();
        $image = $this->attachImage($product);
        $oldPath = $image->storage_path;

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.products.images.replace', [$product, $image]), [
                'image' => $this->png('replacement.png'),
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $image->refresh();

        $this->assertNotSame($oldPath, $image->storage_path);
        Storage::disk('supabase')->assertMissing($oldPath);
        Storage::disk('supabase')->assertExists($image->storage_path);
        $this->assertSame('replacement.png', $image->original_name);
    }

    public function test_images_can_be_reordered(): void
    {
        $product = $this->makeProduct();
        $first = $this->attachImage($product);
        $second = $this->attachImage($product);

        $this->actingAs(User::factory()->admin()->create())
            ->patch(route('admin.products.images.move', [$product, $second]), [
                'direction' => 'up',
            ])
            ->assertRedirect();

        $this->assertSame(1, $second->refresh()->sort_order);
        $this->assertSame(2, $first->refresh()->sort_order);
    }

    public function test_an_image_cannot_be_managed_through_another_product(): void
    {
        $product = $this->makeProduct();
        $other = $this->makeProduct(['slug' => 'other-product', 'name' => 'منتج آخر']);
        $image = $this->attachImage($product);

        $this->actingAs(User::factory()->admin()->create())
            ->delete(route('admin.products.images.destroy', [$other, $image]))
            ->assertNotFound();
    }

    public function test_administrators_can_upload_a_draft_image_before_the_product_exists(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->postJson(route('admin.products.images.draft'), [
                'image' => $this->png('draft.png'),
            ]);

        $response->assertOk()->assertJson(['success' => true]);
        $response->assertJsonStructure(['success', 'key', 'url', 'name', 'message']);

        $key = $response->json('key');

        $this->assertDatabaseCount('products', 0);

        $drafts = session(ProductImageController::DRAFT_SESSION_KEY);
        $this->assertIsArray($drafts);
        $this->assertArrayHasKey($key, $drafts);
        Storage::disk('supabase')->assertExists($drafts[$key]['storage_path']);
    }

    public function test_guests_cannot_upload_draft_images(): void
    {
        $this->post(route('admin.products.images.draft'), [
            'image' => $this->png('draft.png'),
        ])->assertRedirect(route('admin.login'));
    }

    public function test_draft_images_can_be_removed_before_publishing(): void
    {
        $admin = User::factory()->admin()->create();

        $key = $this->actingAs($admin)
            ->postJson(route('admin.products.images.draft'), ['image' => $this->png('draft.png')])
            ->json('key');

        $path = session(ProductImageController::DRAFT_SESSION_KEY)[$key]['storage_path'];

        $this->actingAs($admin)
            ->deleteJson(route('admin.products.images.draft.destroy', $key))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertArrayNotHasKey($key, session(ProductImageController::DRAFT_SESSION_KEY, []));
        Storage::disk('supabase')->assertMissing($path);
    }

    public function test_draft_uploads_reject_non_image_files(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->postJson(route('admin.products.images.draft'), [
                'image' => UploadedFile::fake()->createWithContent('notes.txt', 'not an image'),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('image');

        $this->assertEmpty(session(ProductImageController::DRAFT_SESSION_KEY, []));
    }

    private function png(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $name,
            base64_decode(self::ONE_PIXEL_PNG),
        );
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeProduct(array $attributes = []): Product
    {
        $category = Category::query()->firstOrCreate(
            ['slug' => 'test-category'],
            [
                'name' => 'تصنيف تجريبي',
                'description' => null,
                'is_active' => true,
            ],
        );

        return Product::query()->create(array_merge([
            'category_id' => $category->id,
            'name' => 'منتج تجريبي',
            'slug' => 'test-product',
            'description' => null,
            'price' => '10.50',
            'status' => 'available',
        ], $attributes));
    }

    private function attachImage(Product $product): ProductImage
    {
        $path = 'products/'.$product->id.'/'.uniqid().'.png';

        Storage::disk('supabase')->put($path, base64_decode(self::ONE_PIXEL_PNG));

        return $product->images()->create([
            'storage_path' => $path,
            'public_url' => 'https://example.test/'.$path,
            'original_name' => 'photo.png',
            'mime_type' => 'image/png',
            'size_bytes' => 68,
            'sort_order' => (int) $product->images()->max('sort_order') + 1,
            'alt_text' => $product->name,
        ]);
    }
}
