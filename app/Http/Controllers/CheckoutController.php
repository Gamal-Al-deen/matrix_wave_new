<?php

namespace App\Http\Controllers;

use App\Exceptions\OrderCreationException;
use App\Http\Requests\Checkout\PlaceOrderRequest;
use App\Models\Wallet;
use App\Services\Cart\CartService;
use App\Services\Orders\OrderService;
use App\Support\Currency;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function index(Request $request, CartService $cart): View|RedirectResponse
    {
        $summary = $cart->summary();

        if ($redirect = $this->redirectIfNotCheckoutable($summary)) {
            return $redirect;
        }

        $draft = $request->session()->get('checkout.draft');
        $draft = is_array($draft) ? $draft : [];

        return view('checkout.index', [
            'summary' => $summary,
            'currency' => Currency::code(),
            'wallets' => $this->activeWallets(),
            'draft' => $draft,
            'checkoutToken' => $this->checkoutToken($request),
        ]);
    }

    /**
     * Validate the checkout data and create the order atomically.
     */
    public function store(
        PlaceOrderRequest $request,
        CartService $cart,
        OrderService $orders,
    ): RedirectResponse {
        $summary = $cart->summary();

        if ($redirect = $this->redirectIfNotCheckoutable($summary)) {
            return $redirect;
        }

        $validated = $request->validated();
        unset($validated['checkout_token']);

        try {
            $order = $orders->place($request->user(), $validated);
        } catch (OrderCreationException $exception) {
            return redirect()
                ->route('cart.index')
                ->with('error', $exception->getMessage());
        }

        $request->session()->forget(['checkout.draft', 'checkout.token']);

        return redirect()
            ->route('orders.show', $order)
            ->with('order_created', $order->id);
    }

    /**
     * @param  array{lines: array<int, mixed>, has_issues: bool}  $summary
     */
    private function redirectIfNotCheckoutable(array $summary): ?RedirectResponse
    {
        if ($summary['lines'] === []) {
            return redirect()
                ->route('cart.index')
                ->with('error', 'سلتك فارغة. أضف منتجًا واحدًا على الأقل قبل إتمام الطلب.');
        }

        if ($summary['has_issues']) {
            return redirect()
                ->route('cart.index')
                ->with('error', 'بعض المنتجات في سلتك غير متوفرة حاليًا. احذفها أو استبدلها قبل إتمام الطلب.');
        }

        return null;
    }

    /**
     * @return Collection<int, Wallet>
     */
    private function activeWallets(): Collection
    {
        return Wallet::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    private function checkoutToken(Request $request): string
    {
        $token = $request->session()->get('checkout.token');

        if (! is_string($token) || strlen($token) !== (int) config('checkout.token_length', 40)) {
            $token = $this->newToken();
            $request->session()->put('checkout.token', $token);
        }

        return $token;
    }

    private function newToken(): string
    {
        return Str::random((int) config('checkout.token_length', 40));
    }
}
