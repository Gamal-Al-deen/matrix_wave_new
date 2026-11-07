<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Covers the JSON contract used by the add-to-cart animation while keeping
 * the original redirect behaviour for non-JavaScript requests.
 */
class CartAddJsonTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_json_add_returns_the_authoritative_cart_count_and_exact_message(): void
    {
        $product = $this->makeProduct();

        $response = $this->postJson(route('cart.store', $product->slug), ['quantity' => 2]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'تمت إضافة المنتج إلى السلة')
            ->assertJsonPath('cart_count', 2);

        $this->assertSame([$product->id => 2], session('cart'));
    }

    public function test_authenticated_json_add_accumulates_and_returns_the_database_count(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct();

        $this->actingAs($user)
            ->postJson(route('cart.store', $product->slug), ['quantity' => 2])
            ->assertOk()
            ->assertJsonPath('cart_count', 2);

        $this->actingAs($user)
            ->postJson(route('cart.store', $product->slug), ['quantity' => 1])
            ->assertOk()
            ->assertJsonPath('cart_count', 3);

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 3,
        ]);
    }

    public function test_json_add_does_not_flash_a_success_message(): void
    {
        $product = $this->makeProduct();

        $this->postJson(route('cart.store', $product->slug), ['quantity' => 1])
            ->assertOk();

        $this->assertNull(session('status'));
        $this->assertNull(session('success'));
    }

    public function test_unavailable_product_returns_a_json_validation_error_without_a_success_payload(): void
    {
        $product = $this->makeProduct(['status' => 'out_of_stock']);

        $response = $this->postJson(route('cart.store', $product->slug), ['quantity' => 1]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('quantity')
            ->assertJsonMissingPath('cart_count');

        $this->assertNull(session('cart'));
        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_non_json_add_keeps_the_redirect_and_flash_behaviour(): void
    {
        $product = $this->makeProduct();

        $this->post(route('cart.store', $product->slug), ['quantity' => 1])
            ->assertRedirect()
            ->assertSessionHas('status', 'تمت إضافة المنتج إلى السلة.');
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
