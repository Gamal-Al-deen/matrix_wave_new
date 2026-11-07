<?php

namespace App\Http\Controllers;

use App\Http\Requests\Cart\AddToCartRequest;
use App\Http\Requests\Cart\UpdateCartRequest;
use App\Models\Product;
use App\Services\Cart\CartService;
use App\Support\Currency;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class CartController extends Controller
{
    public function index(CartService $cart): View
    {
        return view('cart.index', [
            'summary' => $cart->summary(),
            'currency' => Currency::code(),
        ]);
    }

    public function store(
        AddToCartRequest $request,
        Product $product,
        CartService $cart,
    ): RedirectResponse|JsonResponse {
        $cart->add($product, $request->integer('quantity', 1));

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'تمت إضافة المنتج إلى السلة',
                'cart_count' => $cart->count(),
            ]);
        }

        return back()->with('status', 'تمت إضافة المنتج إلى السلة.');
    }

    public function update(
        UpdateCartRequest $request,
        string $productId,
        CartService $cart,
    ): RedirectResponse {
        $cart->update($productId, $request->integer('quantity'));

        return back()->with('status', 'تم تحديث كمية المنتج.');
    }

    public function destroy(string $productId, CartService $cart): RedirectResponse
    {
        $cart->remove($productId);

        return redirect()
            ->route('cart.index')
            ->with('status', 'تم حذف المنتج من السلة.');
    }
}
