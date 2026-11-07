<?php

namespace Tests\Feature\Admin;

use App\Enums\ProductStatus;
use App\Http\Controllers\Admin\ProductImageController;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('supabase');
    }

    public function test_guests_are_redirected_from_product_management(): void
    {
        $this->get(route('admin.products.index'))->assertRedirect(route('admin.login'));
    }

    public function test_customers_cannot_manage_products(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.products.index'))
            ->assertForbidden();
    }

    public function test_administrators_can_view_products_including_soft_deleted_ones(): void
    {
        $category = $this->makeCategory();
        $product = $this->makeProduct($category, ['name' => 'منتج ظاهر']);
        $deleted = $this->makeProduct($category, ['slug' => 'deleted-product', 'name' => 'منتج محذوف']);
        $deleted->delete();

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.products.index'))
            ->assertOk()
            ->assertSee('منتج ظاهر')
            ->assertDontSee('منتج محذوف');

        $this->actingAs($admin)
            ->get(route('admin.products.index', ['trashed' => 1]))
            ->assertOk()
            ->assertSee('منتج محذوف');

        $this->assertNotSoftDeleted($product);
    }

    public function test_administrators_can_open_the_product_forms(): void
    {
        $product = $this->makeProduct($this->makeCategory(), ['name' => 'منتج للعرض']);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.products.create'))
            ->assertOk()
            ->assertSee('إضافة منتج')
            ->assertSee('نشر المنتج');

        $this->actingAs($admin)
            ->get(route('admin.products.edit', $product))
            ->assertOk()
            ->assertSee('منتج للعرض')
            ->assertSee('صور المنتج');
    }

    public function test_administrators_can_create_a_product(): void
    {
        $category = $this->makeCategory();
        $key = 'draft-key-create';

        $response = $this->withSession([
            ProductImageController::DRAFT_SESSION_KEY => [$key => $this->draftImage()],
        ])->actingAs(User::factory()->admin()->create())
            ->post(route('admin.products.store'), [
                'category_id' => $category->id,
                'name' => 'هاتف جديد',
                'description' => 'وصف المنتج',
                'price' => '199.99',
                'status' => 'available',
                'draft_images' => [$key],
            ]);

        $product = Product::query()->sole();

        $response->assertRedirect(route('admin.products.index'));
        $response->assertSessionHas('status', 'تم إضافة المنتج بنجاح');

        $this->assertSame('هاتف جديد', $product->name);
        $this->assertSame('199.99', $product->price);
        $this->assertSame('available', $product->status->value);
        $this->assertSame($category->id, $product->category_id);
        $this->assertNotEmpty($product->slug);
        $this->assertSame(1, $product->images()->count());
    }

    public function test_creating_a_product_without_an_image_is_rejected(): void
    {
        $category = $this->makeCategory();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.products.store'), [
                'category_id' => $category->id,
                'name' => 'منتج بدون صورة',
                'price' => '10.00',
                'status' => 'available',
            ])
            ->assertSessionHasErrors('draft_images');

        $this->assertDatabaseCount('products', 0);
    }

    public function test_product_slugs_are_made_unique_automatically(): void
    {
        $category = $this->makeCategory();
        $this->makeProduct($category, ['slug' => 'phone']);
        $key = 'draft-key-slug';

        $this->withSession([
            ProductImageController::DRAFT_SESSION_KEY => [$key => $this->draftImage()],
        ])->actingAs(User::factory()->admin()->create())
            ->post(route('admin.products.store'), [
                'category_id' => $category->id,
                'name' => 'Phone',
                'price' => '10',
                'status' => 'available',
                'draft_images' => [$key],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('products', ['slug' => 'phone-2']);
    }

    public function test_product_validation_rejects_invalid_values(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.products.store'), [
                'category_id' => 'not-a-uuid',
                'name' => '',
                'price' => '-5',
                'status' => 'unknown',
            ])
            ->assertSessionHasErrors(['category_id', 'name', 'price', 'status']);
    }

    public function test_product_price_must_have_at_most_two_decimals(): void
    {
        $category = $this->makeCategory();
        $key = 'draft-key-price';

        $this->withSession([
            ProductImageController::DRAFT_SESSION_KEY => [$key => $this->draftImage()],
        ])->actingAs(User::factory()->admin()->create())
            ->post(route('admin.products.store'), [
                'category_id' => $category->id,
                'name' => 'منتج',
                'price' => '10.999',
                'status' => 'available',
                'draft_images' => [$key],
            ])
            ->assertSessionHasErrors('price')
            ->assertSessionDoesntHaveErrors('draft_images');
    }

    public function test_administrators_can_update_a_product(): void
    {
        $category = $this->makeCategory();
        $product = $this->makeProduct($category);

        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.products.update', $product), [
                'category_id' => $category->id,
                'name' => 'اسم جديد',
                'slug' => 'new-name',
                'description' => 'وصف محدّث',
                'price' => '20.00',
                'status' => 'out_of_stock',
            ])
            ->assertRedirect(route('admin.products.edit', $product));

        $product->refresh();

        $this->assertSame('اسم جديد', $product->name);
        $this->assertSame('out_of_stock', $product->status->value);
    }

    public function test_deleting_a_product_is_soft_and_can_be_restored(): void
    {
        $product = $this->makeProduct($this->makeCategory());

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->delete(route('admin.products.destroy', $product))
            ->assertRedirect(route('admin.products.index'));

        $this->assertSoftDeleted('products', ['id' => $product->id]);

        $this->actingAs($admin)
            ->patch(route('admin.products.restore', $product->id))
            ->assertRedirect(route('admin.products.index'));

        $this->assertNotSoftDeleted('products', ['id' => $product->id]);
    }

    public function test_soft_deleted_products_are_excluded_from_normal_queries(): void
    {
        $product = $this->makeProduct($this->makeCategory());
        $product->delete();

        $this->assertNull(Product::query()->find($product->id));
        $this->assertNotNull(Product::withTrashed()->find($product->id));
    }

    public function test_only_available_products_are_orderable(): void
    {
        $this->assertTrue(ProductStatus::Available->isOrderable());
        $this->assertFalse(ProductStatus::OutOfStock->isOrderable());
        $this->assertFalse(ProductStatus::Inactive->isOrderable());
    }

    private function makeCategory(): Category
    {
        return Category::query()->create([
            'name' => 'تصنيف تجريبي',
            'slug' => 'test-category',
            'description' => null,
            'is_active' => true,
        ]);
    }

    /**
     * Build a stored-image payload matching what the draft upload endpoint
     * keeps in the session, so publishing can resolve it.
     *
     * @return array<string, mixed>
     */
    private function draftImage(): array
    {
        return [
            'storage_path' => 'products/draft/test.png',
            'public_url' => 'https://example.test/products/draft/test.png',
            'original_name' => 'test.png',
            'mime_type' => 'image/png',
            'size_bytes' => 1024,
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeProduct(Category $category, array $attributes = []): Product
    {
        return Product::query()->create(array_merge([
            'category_id' => $category->id,
            'name' => 'منتج تجريبي',
            'slug' => 'test-product',
            'description' => null,
            'price' => '10.50',
            'status' => 'available',
        ], $attributes));
    }
}
