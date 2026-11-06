<?php

namespace App\Services\Cart;

use App\Enums\ProductStatus;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Owns cart persistence for guests (session) and customers (database).
 *
 * Prices are never stored in the cart; every read uses the product's current
 * authoritative price. Unavailable lines stay visible and flagged so the
 * customer can remove them, but they never contribute to the subtotal.
 */
class CartService
{
    public const SESSION_KEY = 'cart';

    public function add(Product $product, int $quantity = 1): void
    {
        $this->ensureOrderable($product);
        $quantity = $this->normalizeQuantity($quantity);

        if (Auth::check()) {
            DB::transaction(function () use ($product, $quantity): void {
                $cart = $this->lockedDatabaseCart((string) Auth::id());
                $item = $cart->items()->firstOrCreate(
                    ['product_id' => $product->id],
                    ['quantity' => $quantity],
                );

                if (! $item->wasRecentlyCreated) {
                    $item->increment('quantity', $quantity);
                }
            });

            return;
        }

        $items = $this->sessionItems();
        $items[$product->id] = ($items[$product->id] ?? 0) + $quantity;

        session()->put(self::SESSION_KEY, $items);
    }

    public function update(string $productId, int $quantity): void
    {
        $quantity = $this->normalizeQuantity($quantity);

        if (Auth::check()) {
            DB::transaction(function () use ($productId, $quantity): void {
                $this->lockExistingDatabaseCart((string) Auth::id())
                    ?->items()
                    ->where('product_id', $productId)
                    ->update(['quantity' => $quantity]);
            });

            return;
        }

        $items = $this->sessionItems();

        if (array_key_exists($productId, $items)) {
            $items[$productId] = $quantity;
            session()->put(self::SESSION_KEY, $items);
        }
    }

    public function remove(string $productId): void
    {
        if (Auth::check()) {
            DB::transaction(function () use ($productId): void {
                $this->lockExistingDatabaseCart((string) Auth::id())
                    ?->items()
                    ->where('product_id', $productId)
                    ->delete();
            });

            return;
        }

        $items = $this->sessionItems();
        unset($items[$productId]);
        session()->put(self::SESSION_KEY, $items);
    }

    public function clear(): void
    {
        if (Auth::check()) {
            DB::transaction(function (): void {
                $this->lockExistingDatabaseCart((string) Auth::id())
                    ?->items()
                    ->delete();
            });

            return;
        }

        session()->forget(self::SESSION_KEY);
    }

    /**
     * Merge the guest session cart into the customer's database cart.
     */
    public function mergeSessionIntoDatabase(User $user): void
    {
        $items = $this->sessionItems();

        if ($items === []) {
            return;
        }

        DB::transaction(function () use ($user, $items): void {
            $cart = $this->lockedDatabaseCart((string) $user->id);
            $existing = $cart->items()->pluck('quantity', 'product_id')->all();

            foreach ($items as $productId => $quantity) {
                if (! Product::query()->whereKey($productId)->exists()) {
                    continue;
                }

                $cart->items()->updateOrCreate(
                    ['product_id' => $productId],
                    ['quantity' => $this->normalizeQuantity(($existing[$productId] ?? 0) + $quantity)],
                );
            }
        });

        session()->forget(self::SESSION_KEY);
    }

    public function count(): int
    {
        if (! Auth::check()) {
            return array_sum($this->sessionItems());
        }

        return (int) CartItem::query()
            ->whereHas('cart', function (Builder $query): void {
                $query->where('user_id', Auth::id());
            })
            ->sum('quantity');
    }

    /**
     * Build the display summary using authoritative product data.
     *
     * @return array{
     *     lines: list<array{
     *         product_id: string,
     *         product: Product|null,
     *         quantity: int,
     *         unit_price_cents: int|null,
     *         line_total_cents: int,
     *         available: bool,
     *         issue: string|null
     *     }>,
     *     subtotal_cents: int,
     *     quantity: int,
     *     has_issues: bool
     * }
     */
    public function summary(): array
    {
        $lines = [];

        if (Auth::check()) {
            $cart = Cart::query()->where('user_id', Auth::id())->first();
            $items = $cart?->items()->with(['product.category', 'product.images'])->get() ?? collect();

            foreach ($items as $item) {
                $lines[] = $this->makeLine($item->product, $item->quantity, $item->product_id);
            }
        } else {
            $sessionItems = $this->sessionItems();
            $products = Product::query()
                ->with(['category', 'images'])
                ->whereKey(array_keys($sessionItems))
                ->get()
                ->keyBy('id');

            foreach ($sessionItems as $productId => $quantity) {
                $lines[] = $this->makeLine($products->get($productId), $quantity, $productId);
            }
        }

        $subtotalCents = 0;
        $totalQuantity = 0;
        $hasIssues = false;

        foreach ($lines as $line) {
            $totalQuantity += $line['quantity'];

            if ($line['available']) {
                $subtotalCents += $line['line_total_cents'];
            } else {
                $hasIssues = true;
            }
        }

        return [
            'lines' => $lines,
            'subtotal_cents' => $subtotalCents,
            'quantity' => $totalQuantity,
            'has_issues' => $hasIssues,
        ];
    }

    private function ensureOrderable(Product $product): void
    {
        $product->loadMissing('category');

        if ($product->status !== ProductStatus::Available || ! $product->category?->is_active) {
            throw ValidationException::withMessages([
                'quantity' => 'لا يمكن إضافة هذا المنتج لأنه غير متوفر للطلب حاليًا.',
            ]);
        }
    }

    /**
     * @return array<string, int>
     */
    private function sessionItems(): array
    {
        $raw = session()->get(self::SESSION_KEY, []);

        if (! is_array($raw)) {
            return [];
        }

        $items = [];

        foreach ($raw as $productId => $quantity) {
            if (! is_string($productId) || ! is_numeric($quantity)) {
                continue;
            }

            $quantity = (int) $quantity;

            if ($quantity > 0) {
                $items[$productId] = $quantity;
            }
        }

        return $items;
    }

    private function databaseCart(string $userId): Cart
    {
        return Cart::query()->firstOrCreate(['user_id' => $userId]);
    }

    /**
     * Lock the customer's cart for a mutation so checkout cannot interleave.
     */
    private function lockedDatabaseCart(string $userId): Cart
    {
        $cart = $this->databaseCart($userId);

        return Cart::query()->whereKey($cart->id)->lockForUpdate()->firstOrFail();
    }

    private function lockExistingDatabaseCart(string $userId): ?Cart
    {
        return Cart::query()
            ->where('user_id', $userId)
            ->lockForUpdate()
            ->first();
    }

    private function normalizeQuantity(int $quantity): int
    {
        $max = (int) config('cart.max_quantity', 1000000);

        return min($max, max(1, $quantity));
    }

    /**
     * @return array{
     *     product_id: string,
     *     product: Product|null,
     *     quantity: int,
     *     unit_price_cents: int|null,
     *     line_total_cents: int,
     *     available: bool,
     *     issue: string|null
     * }
     */
    private function makeLine(?Product $product, int $quantity, string $productId): array
    {
        $quantity = $this->normalizeQuantity($quantity);

        if ($product === null) {
            return [
                'product_id' => $productId,
                'product' => null,
                'quantity' => $quantity,
                'unit_price_cents' => null,
                'line_total_cents' => 0,
                'available' => false,
                'issue' => 'المنتج لم يعد متوفرًا في المتجر.',
            ];
        }

        $product->loadMissing('category');

        $unitPriceCents = (int) round(((float) $product->price) * 100);
        $available = true;
        $issue = null;

        if ($product->status === ProductStatus::Inactive) {
            $available = false;
            $issue = 'المنتج غير مفعّل حاليًا ولا يمكن طلبه.';
        } elseif ($product->status === ProductStatus::OutOfStock) {
            $available = false;
            $issue = 'المنتج غير متوفر حاليًا. يمكنك حذفه أو المحاولة لاحقًا.';
        } elseif (! $product->category?->is_active) {
            $available = false;
            $issue = 'التصنيف المرتبط بهذا المنتج غير مفعّل حاليًا.';
        }

        return [
            'product_id' => $productId,
            'product' => $product,
            'quantity' => $quantity,
            'unit_price_cents' => $unitPriceCents,
            'line_total_cents' => $unitPriceCents * $quantity,
            'available' => $available,
            'issue' => $issue,
        ];
    }
}
