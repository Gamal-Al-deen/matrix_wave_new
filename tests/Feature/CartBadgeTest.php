<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guards the server-rendered cart badge contract used by the header and the
 * mobile bottom navigation: the badge must be hidden at zero and must carry
 * the authoritative cart quantity once the cart has items.
 */
class CartBadgeTest extends TestCase
{
    use RefreshDatabase;

    public function test_cart_badges_are_hidden_when_the_cart_is_empty(): void
    {
        $html = (string) $this->get(route('home'))->assertOk()->getContent();

        $tags = $this->cartBadgeTags($html);

        // The header cart link and the bottom navigation cart link both render a badge.
        $this->assertGreaterThanOrEqual(2, count($tags));

        foreach ($tags as $tag) {
            $this->assertContains('hidden', $this->cartBadgeClasses($tag), "Empty cart badge must be hidden: {$tag}");
        }
    }

    public function test_cart_badges_show_the_quantity_and_stay_visible_when_the_cart_has_items(): void
    {
        $product = $this->makeProduct();

        $html = (string) $this->withSession(['cart' => [$product->id => 2]])
            ->get(route('home'))
            ->assertOk()
            ->getContent();

        $tags = $this->cartBadgeTags($html);

        $this->assertGreaterThanOrEqual(2, count($tags));

        foreach ($tags as $tag) {
            $this->assertNotContains('hidden', $this->cartBadgeClasses($tag), "Non-empty cart badge must be visible: {$tag}");
        }

        $values = $this->cartBadgeValues($html);

        $this->assertGreaterThanOrEqual(2, count($values));

        foreach ($values as $value) {
            $this->assertSame('2', $value);
        }
    }

    /**
     * @return list<string>
     */
    private function cartBadgeTags(string $html): array
    {
        preg_match_all('/<span[^>]*data-cart-count[^>]*>/', $html, $matches);

        return $matches[0];
    }

    /**
     * The `hidden` class tokens of a badge opening tag (never the `aria-hidden`
     * attribute, which would otherwise produce a false positive).
     *
     * @return list<string>
     */
    private function cartBadgeClasses(string $tag): array
    {
        preg_match('/\bclass="([^"]*)"/', $tag, $matches);

        $classes = preg_split('/\s+/', trim($matches[1] ?? ''));

        return array_values(array_filter($classes ?: [], static fn (string $class): bool => $class !== ''));
    }

    /**
     * @return list<string>
     */
    private function cartBadgeValues(string $html): array
    {
        preg_match_all('/<span[^>]*data-cart-count[^>]*>\s*([0-9]+)\s*<\/span>/', $html, $matches);

        return $matches[1];
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
