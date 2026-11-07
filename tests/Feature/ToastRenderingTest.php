<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifies that temporary flash messages are rendered through the unified
 * toast region instead of the old inline alert blocks.
 */
class ToastRenderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_storefront_flash_message_is_rendered_inside_the_toast_region(): void
    {
        $response = $this->withSession([
            'status' => 'تم حفظ بيانات حسابك بنجاح.',
        ])->get(route('home'));

        $response->assertOk()
            ->assertSee('data-toast-region', false)
            ->assertSee('class="toast toast--success"', false)
            ->assertSee('تم حفظ بيانات حسابك بنجاح.');
    }

    public function test_storefront_error_flash_uses_the_error_toast(): void
    {
        $response = $this->withSession([
            'error' => 'سلتك فارغة. أضف منتجًا واحدًا على الأقل قبل إتمام الطلب.',
        ])->get(route('home'));

        $response->assertOk()
            ->assertSee('class="toast toast--error"', false)
            ->assertSee('سلتك فارغة. أضف منتجًا واحدًا على الأقل قبل إتمام الطلب.');
    }

    public function test_validation_errors_are_shown_once_in_a_toast_list(): void
    {
        $product = $this->makeProduct();

        $response = $this->from(route('products.show', $product->slug))
            ->followingRedirects()
            ->post(route('cart.store', $product->slug), ['quantity' => 0]);

        $response->assertOk()
            ->assertSee('toast__message--list', false)
            ->assertSee('toast--error', false)
            ->assertDontSee('list-inside list-disc space-y-1', false);
    }

    public function test_admin_layout_renders_the_toast_region(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->withSession(['status' => 'تم تحديث حالة الطلب.'])
            ->get(route('admin.dashboard'));

        $response->assertOk()
            ->assertSee('data-toast-region', false)
            ->assertSee('class="toast toast--success"', false)
            ->assertSee('تم تحديث حالة الطلب.');
    }

    public function test_auth_layout_renders_the_toast_region(): void
    {
        $response = $this->withSession([
            'status' => 'تم تسجيل الخروج بنجاح.',
        ])->get(route('login'));

        $response->assertOk()
            ->assertSee('data-toast-region', false)
            ->assertSee('class="toast toast--success"', false)
            ->assertSee('تم تسجيل الخروج بنجاح.');
    }

    public function test_auth_layout_renders_validation_errors_in_the_toast_list(): void
    {
        $response = $this->followingRedirects()->post('/login', [
            'email' => 'missing-user@example.test',
            'password' => 'wrong-password',
        ]);

        $response->assertOk()
            ->assertSee('toast__message--list', false)
            ->assertSee('class="toast toast--error"', false)
            ->assertSee('بيانات الدخول غير مطابقة لسجلاتنا.');
    }

    public function test_toast_region_is_rendered_only_once_per_page(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();

        $this->assertSame(1, substr_count($response->getContent(), 'data-toast-region'));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeProduct(array $attributes = []): Product
    {
        $category = Category::query()->create([
            'name' => 'تصنيف تجريبي',
            'slug' => 'test-category-'.uniqid(),
            'description' => null,
            'is_active' => true,
        ]);

        return Product::query()->create(array_merge([
            'category_id' => $category->id,
            'name' => 'منتج تجريبي',
            'slug' => 'test-product-'.uniqid(),
            'description' => null,
            'price' => '10.00',
            'status' => 'available',
        ], $attributes));
    }
}
