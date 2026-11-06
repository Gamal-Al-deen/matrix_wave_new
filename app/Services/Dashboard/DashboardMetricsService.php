<?php

namespace App\Services\Dashboard;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Calculates the dashboard indicators with aggregate SQL queries.
 *
 * Monetary values are grouped by the order's saved currency instead of being
 * added across currencies without an exchange rate.
 */
class DashboardMetricsService
{
    /**
     * @return array{
     *     orders: int,
     *     customers: int,
     *     products: int,
     *     operational_value: array<string, string>,
     *     confirmed_sales: array<string, string>,
     *     paid_sales: array<string, string>,
     *     completed_value: array<string, string>
     * }
     */
    public function metrics(string $fallbackCurrency = 'USD'): array
    {
        $counts = $this->counts();
        $amounts = $this->amountsByCurrency();

        return [
            'orders' => (int) ($counts->orders_count ?? 0),
            'customers' => (int) ($counts->customers_count ?? 0),
            'products' => (int) ($counts->products_count ?? 0),
            'operational_value' => $this->formatAmounts($amounts['operational'], $fallbackCurrency),
            'confirmed_sales' => $this->formatAmounts($amounts['confirmed'], $fallbackCurrency),
            'paid_sales' => $this->formatAmounts($amounts['paid'], $fallbackCurrency),
            'completed_value' => $this->formatAmounts($amounts['completed'], $fallbackCurrency),
        ];
    }

    /**
     * Read the three counters in a single round trip.
     *
     * Eloquent subqueries keep the model global scopes (notably the
     * soft-delete scope on products) in effect.
     */
    private function counts(): object
    {
        return DB::query()
            ->selectSub(Order::query()->selectRaw('count(*)'), 'orders_count')
            ->selectSub(
                User::query()->where('role', UserRole::Customer->value)->selectRaw('count(*)'),
                'customers_count',
            )
            ->selectSub(Product::query()->selectRaw('count(*)'), 'products_count')
            ->first() ?? (object) [];
    }

    /**
     * Group the order totals by currency and fulfillment/payment state so every
     * dashboard metric can be composed from the same single scan.
     *
     * @return array{
     *     operational: array<string, int>,
     *     confirmed: array<string, int>,
     *     paid: array<string, int>,
     *     completed: array<string, int>
     * }
     */
    private function amountsByCurrency(): array
    {
        $rows = DB::table('orders')
            ->selectRaw('currency_code, status, payment_status, SUM(total) as total')
            ->groupBy('currency_code', 'status', 'payment_status')
            ->orderBy('currency_code')
            ->get();

        $confirmedStatuses = [
            OrderStatus::Confirmed->value,
            OrderStatus::Preparing->value,
            OrderStatus::OutForDelivery->value,
            OrderStatus::Completed->value,
        ];

        $cancelled = OrderStatus::Cancelled->value;
        $paid = PaymentStatus::Paid->value;
        $completed = OrderStatus::Completed->value;

        $amounts = [
            'operational' => [],
            'confirmed' => [],
            'paid' => [],
            'completed' => [],
        ];

        foreach ($rows as $row) {
            $currency = (string) $row->currency_code;
            $status = (string) $row->status;
            $cents = $this->toCents($row->total);

            if ($status !== $cancelled) {
                $amounts['operational'][$currency] = ($amounts['operational'][$currency] ?? 0) + $cents;
            }

            if (in_array($status, $confirmedStatuses, true)) {
                $amounts['confirmed'][$currency] = ($amounts['confirmed'][$currency] ?? 0) + $cents;
            }

            if ($status !== $cancelled && (string) $row->payment_status === $paid) {
                $amounts['paid'][$currency] = ($amounts['paid'][$currency] ?? 0) + $cents;
            }

            if ($status === $completed) {
                $amounts['completed'][$currency] = ($amounts['completed'][$currency] ?? 0) + $cents;
            }
        }

        return $amounts;
    }

    /**
     * @param  array<string, int>  $cents
     * @return array<string, string>
     */
    private function formatAmounts(array $cents, string $fallbackCurrency): array
    {
        if ($cents === []) {
            $fallbackCurrency = trim($fallbackCurrency);

            return [($fallbackCurrency !== '' ? $fallbackCurrency : 'USD') => '0.00'];
        }

        $amounts = [];

        foreach ($cents as $currency => $amount) {
            $amounts[$currency] = number_format($amount / 100, 2, '.', '');
        }

        return $amounts;
    }

    /**
     * Store money as integer cents to keep the additions exact.
     */
    private function toCents(mixed $amount): int
    {
        return (int) round((float) ($amount ?? 0) * 100);
    }
}
