<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_can_add_products_and_duplicate_additions_increment_the_line(): void
    {
        $product = $this->makeProduct(['price' => '10.00']);

        $this->post(route('cart.store', $product->slug), ['quantity' => 2])->assertRedirect();

        $this->assertSame([$product->id => 2], session('cart'));

        $this->post(route('cart.store', $product->slug), ['quantity' => 1])->assertRedirect();

        $this->assertSame([$product->id => 3], session('cart'));

        $this->get(route('cart.index'))
            ->assertOk()
            ->assertSee('سلة المشتريات')
            ->assertSee('منتج تجريبي')
            ->assertSee('عدد العناصر')
            ->assertSee('30.00 USD')
            ->assertSee('متابعة التسوق')
            ->assertSee('>3<', false);
    }

    public function test_client_supplied_prices_are_ignored(): void
    {
        $product = $this->makeProduct(['price' => '10.00']);

        $this->post(route('cart.store', $product->slug), [
            'quantity' => 1,
            'price' => '0.01',
        ])->assertRedirect();

        $this->assertSame([$product->id => 1], session('cart'));

        $this->get(route('cart.index'))
            ->assertOk()
            ->assertSee('10.00 USD')
            ->assertDontSee('0.01 USD');
    }

    public function test_out_of_stock_inactive_and_hidden_category_products_cannot_be_added(): void
    {
        $category = $this->makeCategory();
        $inactiveCategory = $this->makeCategory(['is_active' => false, 'slug' => 'hidden-category']);

        $outOfStock = $this->makeProduct(['slug' => 'out-of-stock', 'status' => 'out_of_stock']);
        $inactive = $this->makeProduct(['slug' => 'inactive-product', 'status' => 'inactive']);
        $hidden = $this->makeProduct(['slug' => 'hidden-product', 'category_id' => $inactiveCategory->id]);

        foreach ([$outOfStock, $inactive, $hidden] as $product) {
            $this->post(route('cart.store', $product->slug), ['quantity' => 1])
                ->assertSessionHasErrors('quantity');
        }

        $this->assertNull(session('cart'));
        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_guests_can_update_and_remove_cart_quantities(): void
    {
        $product = $this->makeProduct();

        $this->post(route('cart.store', $product->slug), ['quantity' => 1]);

        $this->patch(route('cart.update', $product->id), ['quantity' => 4])
            ->assertRedirect();

        $this->assertSame([$product->id => 4], session('cart'));

        $this->delete(route('cart.destroy', $product->id))
            ->assertRedirect(route('cart.index'))
            ->assertSessionHas('status', 'تم حذف المنتج من السلة.');

        $this->assertSame([], session('cart'));

        $this->get(route('cart.index'))
            ->assertOk()
            ->assertSee('سلتك فارغة');
    }

    public function test_cart_quantities_must_be_positive_integers(): void
    {
        $product = $this->makeProduct();

        $this->post(route('cart.store', $product->slug), ['quantity' => 2]);

        $this->patch(route('cart.update', $product->id), ['quantity' => 0])
            ->assertSessionHasErrors('quantity');

        $this->patch(route('cart.update', $product->id), ['quantity' => 'abc'])
            ->assertSessionHasErrors('quantity');

        $this->assertSame([$product->id => 2], session('cart'));
    }

    public function test_authenticated_customers_use_a_database_cart(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct(['price' => '12.50']);

        $this->actingAs($user)
            ->post(route('cart.store', $product->slug), ['quantity' => 2])
            ->assertRedirect();

        $this->assertNull(session('cart'));
        $this->assertDatabaseHas('carts', ['user_id' => $user->id]);
        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $this->actingAs($user)
            ->post(route('cart.store', $product->slug), ['quantity' => 1]);

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 3,
        ]);

        $this->actingAs($user)
            ->get(route('cart.index'))
            ->assertOk()
            ->assertSee('37.50 USD');
    }

    public function test_login_merges_the_guest_cart_into_the_customer_cart_by_product(): void
    {
        $user = User::factory()->create(['email' => 'customer@example.com']);
        $first = $this->makeProduct(['name' => 'المنتج الأول', 'slug' => 'first-product']);
        $second = $this->makeProduct(['name' => 'المنتج الثاني', 'slug' => 'second-product']);

        $cart = Cart::query()->create(['user_id' => $user->id]);
        $cart->items()->create(['product_id' => $first->id, 'quantity' => 3]);

        $this->withSession(['cart' => [
            $first->id => 2,
            $second->id => 1,
        ]])->post('/login', [
            'email' => 'customer@example.com',
            'password' => 'password',
        ])->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user);
        $this->assertNull(session('cart'));
        $this->assertDatabaseHas('cart_items', [
            'cart_id' => $cart->id,
            'product_id' => $first->id,
            'quantity' => 5,
        ]);
        $this->assertDatabaseHas('cart_items', [
            'cart_id' => $cart->id,
            'product_id' => $second->id,
            'quantity' => 1,
        ]);
    }

    public function test_registration_merges_the_guest_cart(): void
    {
        $product = $this->makeProduct();

        $this->withSession(['cart' => [$product->id => 2]])
            ->post('/register', [
                'name' => 'عميل جديد',
                'email' => 'new-customer@example.com',
                'phone' => '0500000000',
                'password' => 'strong-password-123',
                'password_confirmation' => 'strong-password-123',
            ])->assertRedirect(route('home'));

        $user = User::query()->where('email', 'new-customer@example.com')->firstOrFail();

        $this->assertNull(session('cart'));
        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
        $this->assertDatabaseHas('carts', ['user_id' => $user->id]);
    }

    public function test_cart_revalidates_availability_and_excludes_unavailable_lines_from_the_subtotal(): void
    {
        $available = $this->makeProduct(['name' => 'متاح', 'slug' => 'available-product', 'price' => '10.00']);
        $unavailable = $this->makeProduct(['name' => 'نافد', 'slug' => 'now-out-of-stock', 'price' => '5.00']);

        $this->post(route('cart.store', $available->slug), ['quantity' => 2]);
        $this->post(route('cart.store', $unavailable->slug), ['quantity' => 1]);

        $unavailable->update(['status' => 'out_of_stock']);

        $this->get(route('cart.index'))
            ->assertOk()
            ->assertSee('نافد')
            ->assertSee('المنتج غير متوفر حاليًا')
            ->assertSee('20.00 USD')
            ->assertDontSee('25.00 USD')
            ->assertSee('بعض المنتجات في سلتك غير متوفرة حاليًا');
    }

    public function test_deleted_products_remain_removable_from_the_cart(): void
    {
        $product = $this->makeProduct();

        $this->post(route('cart.store', $product->slug), ['quantity' => 1]);

        $product->delete();

        $this->get(route('cart.index'))
            ->assertOk()
            ->assertSee('المنتج لم يعد متوفرًا في المتجر.')
            ->assertSee('حذف من السلة');

        $this->delete(route('cart.destroy', $product->id))
            ->assertRedirect(route('cart.index'));

        $this->assertSame([], session('cart'));
    }

    public function test_merged_session_items_for_deleted_products_are_skipped(): void
    {
        $user = User::factory()->create(['email' => 'customer@example.com']);
        $product = $this->makeProduct();
        $product->delete();

        $this->withSession(['cart' => [$product->id => 2]])
            ->post('/login', [
                'email' => 'customer@example.com',
                'password' => 'password',
            ])->assertRedirect(route('home'));

        $this->assertDatabaseCount('cart_items', 0);
        $this->assertNull(session('cart'));
    }

    public function test_product_details_offer_an_add_to_cart_form(): void
    {
        $product = $this->makeProduct(['slug' => 'orderable-product']);

        $this->get(route('products.show', $product->slug))
            ->assertOk()
            ->assertSee('action="'.route('cart.store', $product->slug).'"', false)
            ->assertSee('أضف إلى السلة');
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeCategory(array $attributes = []): Category
    {
        return Category::query()->create(array_merge([
            'name' => 'تصنيف تجريبي',
            'slug' => 'test-category-'.uniqid(),
            'description' => null,
            'is_active' => true,
        ], $attributes));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeProduct(array $attributes = []): Product
    {
        $category = Category::query()->find($attributes['category_id'] ?? null) ?? $this->makeCategory();

        return Product::query()->create(array_merge([
            'category_id' => $category->id,
            'name' => 'منتج تجريبي',
            'slug' => 'test-product',
            'description' => null,
            'price' => '10.00',
            'status' => 'available',
        ], $attributes));
    }
}
