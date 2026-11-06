<?php

namespace App\Services\Orders;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Enums\UserRole;
use App\Exceptions\OrderCreationException;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Wallet;
use App\Notifications\NewOrderPlacedNotification;
use App\Support\Currency;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Creates an order and its historical snapshots in one database transaction.
 *
 * Cart rows are locked first so two concurrent submissions for the same cart
 * cannot both create an order. Product and category rows are locked before
 * their current status and price are read.
 */
class OrderService
{
    /**
     * Maximum value that fits the numeric(12,2) order columns.
     */
    private const MAX_TOTAL_CENTS = 999_999_999_999;

    /**
     * @param  array{
     *     name: string,
     *     phone: string,
     *     email?: string|null,
     *     delivery_address: string,
     *     wallet_id?: string|null,
     *     notes?: string|null
     * }  $data
     */
    public function place(User $user, array $data): Order
    {
        $order = DB::transaction(function () use ($user, $data): Order {
            $cart = Cart::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if ($cart === null) {
                throw new OrderCreationException('سلتك فارغة. أضف منتجًا واحدًا على الأقل قبل إتمام الطلب.');
            }

            $cartItems = $cart->items()
                ->orderBy('created_at')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($cartItems->isEmpty()) {
                throw new OrderCreationException('سلتك فارغة. أضف منتجًا واحدًا على الأقل قبل إتمام الطلب.');
            }

            $products = Product::query()
                ->whereKey($cartItems->pluck('product_id')->all())
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $categories = Category::query()
                ->whereKey($products->pluck('category_id')->unique()->all())
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $walletId = $this->walletId($data);

            if ($walletId !== null) {
                $wallet = Wallet::query()
                    ->whereKey($walletId)
                    ->lockForUpdate()
                    ->first();

                if ($wallet === null || ! $wallet->is_active) {
                    throw new OrderCreationException('المحفظة المختارة لم تعد متاحة. اختر محفظة أخرى أو تابع بدون محفظة.');
                }
            }

            $itemRows = [];
            $subtotalCents = 0;

            foreach ($cartItems as $cartItem) {
                $product = $products->get($cartItem->product_id);

                if ($product === null) {
                    throw new OrderCreationException('أحد المنتجات في سلتك لم يعد متوفرًا. احذفه أو استبدله ثم حاول مرة أخرى.');
                }

                $category = $categories->get($product->category_id);

                if ($product->status !== ProductStatus::Available || ! $category?->is_active) {
                    throw new OrderCreationException(
                        sprintf('المنتج "%s" لم يعد متاحًا للطلب. احذفه أو استبدله ثم حاول مرة أخرى.', $product->name),
                    );
                }

                if ($cartItem->quantity < 1) {
                    throw new OrderCreationException('إحدى كميات المنتجات غير صالحة. حدّث السلة ثم حاول مرة أخرى.');
                }

                $unitPriceCents = $this->decimalToCents((string) $product->price);
                $lineTotalCents = $unitPriceCents * $cartItem->quantity;

                if ($lineTotalCents > self::MAX_TOTAL_CENTS - $subtotalCents) {
                    throw new OrderCreationException('إجمالي الطلب يتجاوز الحد الذي يمكن حفظه. راجع الكميات ثم حاول مرة أخرى.');
                }

                $subtotalCents += $lineTotalCents;

                $itemRows[] = [
                    'product_id' => $product->id,
                    'product_name_snapshot' => $product->name,
                    'unit_price' => $this->centsToDecimal($unitPriceCents),
                    'quantity' => $cartItem->quantity,
                    'line_total' => $this->centsToDecimal($lineTotalCents),
                ];
            }

            $order = Order::query()->create([
                'order_number' => $this->orderNumber(),
                'user_id' => $user->id,
                'status' => OrderStatus::Pending,
                'payment_status' => PaymentStatus::Unpaid,
                'wallet_id' => $walletId,
                'currency_code' => Currency::code(),
                'customer_name_snapshot' => $data['name'],
                'customer_phone_snapshot' => $data['phone'],
                'customer_email_snapshot' => $this->nullableString($data['email'] ?? null),
                'delivery_address_snapshot' => $data['delivery_address'],
                'notes' => $this->nullableString($data['notes'] ?? null),
                'subtotal' => $this->centsToDecimal($subtotalCents),
                'delivery_fee' => '0.00',
                'total' => $this->centsToDecimal($subtotalCents),
            ]);

            $order->items()->createMany($itemRows);

            $order->statusHistory()->create([
                'from_status' => null,
                'to_status' => OrderStatus::Pending,
                'changed_by' => $user->id,
                'note' => 'تم إنشاء الطلب.',
            ]);

            $cart->items()->delete();

            return $order->load('items');
        });

        $this->notifyAdmins($order);

        return $order;
    }

    /**
     * Send one persisted notification per administrator for a successful order.
     *
     * Runs only after the order transaction has committed. Notification
     * failures are logged and never affect the already-created order.
     */
    private function notifyAdmins(Order $order): void
    {
        try {
            User::query()
                ->where('role', UserRole::Admin->value)
                ->each(fn (User $admin) => $admin->notify(new NewOrderPlacedNotification($order)));
        } catch (\Throwable $exception) {
            Log::error('Failed to notify administrators about a new order.', [
                'order_id' => $order->getKey(),
                'exception' => $exception,
            ]);
        }
    }

    private function orderNumber(): string
    {
        return 'MW-'.(string) Str::ulid();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function walletId(array $data): ?string
    {
        $walletId = $data['wallet_id'] ?? null;

        return is_string($walletId) && $walletId !== '' ? $walletId : null;
    }

    private function nullableString(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * Convert a decimal string with up to two fractional digits into cents.
     */
    private function decimalToCents(string $value): int
    {
        $value = trim($value);

        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');
        $fraction = str_pad(substr($fraction, 0, 2), 2, '0');

        return ((int) $whole) * 100 + (int) $fraction;
    }

    /**
     * Format integer cents as a decimal string without using floating point.
     */
    private function centsToDecimal(int $cents): string
    {
        return intdiv($cents, 100).'.'.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }
}
