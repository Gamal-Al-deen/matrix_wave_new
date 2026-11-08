<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_category_management(): void
    {
        $this->get(route('admin.categories.index'))->assertRedirect(route('admin.login'));
    }

    public function test_customers_cannot_manage_categories(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.categories.index'))
            ->assertForbidden();
    }

    public function test_administrators_can_view_categories(): void
    {
        $category = $this->makeCategory(['name' => 'إلكترونيات']);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.categories.index'))
            ->assertOk()
            ->assertSee('إلكترونيات')
            ->assertSee($category->slug);
    }

    public function test_administrators_can_open_the_category_forms(): void
    {
        $category = $this->makeCategory(['name' => 'إلكترونيات']);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.categories.create'))
            ->assertOk()
            ->assertSee('إضافة تصنيف');

        $this->actingAs($admin)
            ->get(route('admin.categories.edit', $category))
            ->assertOk()
            ->assertSee('إلكترونيات');
    }

    public function test_administrators_can_create_a_category_with_an_automatic_slug(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.categories.store'), [
                'name' => 'إلكترونيات',
                'description' => 'أجهزة وملحقات',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.categories.index'));

        $category = Category::query()->sole();

        $this->assertNotSame('', $category->slug);
        $this->assertTrue(Str::isAscii($category->slug));
        $this->assertMatchesRegularExpression('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $category->slug);
        $this->assertSame('إلكترونيات', $category->name);
        $this->assertTrue($category->is_active);
    }

    public function test_categories_can_be_created_with_an_explicit_slug(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.categories.store'), [
                'name' => 'Electronics',
                'slug' => 'Electronics-Devices',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.categories.index'));

        $this->assertDatabaseHas('categories', ['slug' => 'electronics-devices']);
    }

    public function test_category_names_are_required_and_slugs_must_be_unique(): void
    {
        $this->makeCategory(['slug' => 'electronics']);

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.categories.store'), [
                'name' => '',
                'slug' => 'electronics',
            ])
            ->assertSessionHasErrors(['name', 'slug']);
    }

    public function test_administrators_can_update_a_category(): void
    {
        $category = $this->makeCategory();

        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.categories.update', $category), [
                'name' => 'اسم محدّث',
                'slug' => 'updated-name',
                'description' => 'وصف جديد',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.categories.edit', $category));

        $category->refresh();

        $this->assertSame('اسم محدّث', $category->name);
        $this->assertSame('updated-name', $category->slug);
        $this->assertSame('وصف جديد', $category->description);
    }

    public function test_administrators_can_toggle_category_status(): void
    {
        $category = $this->makeCategory(['is_active' => true]);

        $this->actingAs(User::factory()->admin()->create())
            ->patch(route('admin.categories.status', $category))
            ->assertRedirect();

        $this->assertFalse($category->refresh()->is_active);
    }

    public function test_categories_with_products_cannot_be_deleted(): void
    {
        $category = $this->makeCategory();
        $this->makeProduct($category);

        $this->actingAs(User::factory()->admin()->create())
            ->delete(route('admin.categories.destroy', $category))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_categories_with_legacy_soft_deleted_products_cannot_be_deleted(): void
    {
        $category = $this->makeCategory();
        $product = $this->makeProduct($category);
        $product->delete();

        $this->actingAs(User::factory()->admin()->create())
            ->delete(route('admin.categories.destroy', $category))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_empty_categories_can_be_deleted(): void
    {
        $category = $this->makeCategory();

        $this->actingAs(User::factory()->admin()->create())
            ->delete(route('admin.categories.destroy', $category))
            ->assertRedirect(route('admin.categories.index'));

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_the_active_scope_returns_only_active_categories(): void
    {
        $active = $this->makeCategory(['slug' => 'active', 'is_active' => true]);
        $inactive = $this->makeCategory(['slug' => 'inactive', 'is_active' => false]);

        $ids = Category::query()->active()->pluck('id');

        $this->assertTrue($ids->contains($active->id));
        $this->assertFalse($ids->contains($inactive->id));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeCategory(array $attributes = []): Category
    {
        return Category::query()->create(array_merge([
            'name' => 'تصنيف تجريبي',
            'slug' => 'test-category',
            'description' => null,
            'is_active' => true,
        ], $attributes));
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
