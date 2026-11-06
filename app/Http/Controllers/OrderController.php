<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Notifications\OrderStatusChangedNotification;
use App\Services\Orders\OrderWhatsAppService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $orders = $request->user()
            ->orders()
            ->withCount('items')
            ->latest('created_at')
            ->paginate(12);

        return view('orders.index', [
            'orders' => $orders,
            'orderNotifications' => $request->user()
                ->notifications()
                ->where('type', OrderStatusChangedNotification::class)
                ->latest('created_at')
                ->limit(6)
                ->get(),
        ]);
    }

    public function show(Request $request, Order $order, OrderWhatsAppService $whatsApp): View
    {
        $this->authorize('view', $order);

        $order->load('items');

        return view('orders.show', [
            'order' => $order,
            'justCreated' => $request->session()->get('order_created') === $order->id,
            'whatsappAvailable' => $whatsApp->isConfigured(),
        ]);
    }

    public function whatsapp(Request $request, Order $order, OrderWhatsAppService $whatsApp): RedirectResponse
    {
        $this->authorize('view', $order);

        $url = $whatsApp->urlFor($order);

        if ($url === null) {
            return redirect()
                ->route('orders.show', $order)
                ->with('error', 'تعذّر فتح واتساب لأن رقم المتجر غير مهيأ.');
        }

        $order->newQuery()
            ->whereKey($order->getKey())
            ->whereNull('whatsapp_opened_at')
            ->update(['whatsapp_opened_at' => now()]);

        return redirect()->away($url);
    }
}
