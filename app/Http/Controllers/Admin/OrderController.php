<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateOrderStatusRequest;
use App\Http\Requests\Admin\UpdatePaymentStatusRequest;
use App\Models\Order;
use App\Services\Orders\AdminOrderIndicator;
use App\Services\Orders\OrderStatusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request, AdminOrderIndicator $indicator): View
    {
        $this->authorize('viewAny', Order::class);

        // Opening the orders list clears the "new orders" indicator for this session.
        $indicator->markViewed($request);

        $status = OrderStatus::tryFrom((string) $request->query('status', ''));
        $paymentStatus = PaymentStatus::tryFrom((string) $request->query('payment_status', ''));
        $search = mb_substr(trim((string) $request->query('q', '')), 0, 100);

        $orders = Order::query()
            ->when($status, fn ($query) => $query->where('status', $status->value))
            ->when($paymentStatus, fn ($query) => $query->where('payment_status', $paymentStatus->value))
            ->when($search !== '', function ($query) use ($search): void {
                $like = '%'.$this->escapeLike($search).'%';

                $query->where(function ($query) use ($like): void {
                    $query
                        ->where('order_number', 'like', $like)
                        ->orWhere('customer_name_snapshot', 'like', $like)
                        ->orWhere('customer_phone_snapshot', 'like', $like)
                        ->orWhere('customer_email_snapshot', 'like', $like);
                });
            })
            ->latest('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.orders.index', [
            'orders' => $orders,
            'status' => $status,
            'paymentStatus' => $paymentStatus,
            'search' => $search,
        ]);
    }

    public function show(Order $order): View
    {
        $this->authorize('view', $order);

        $order->load([
            'items',
            'user',
            'wallet',
            'statusHistory.changedBy',
        ]);

        return view('admin.orders.show', [
            'order' => $order,
            'paymentStatuses' => PaymentStatus::cases(),
        ]);
    }

    public function updateStatus(
        UpdateOrderStatusRequest $request,
        Order $order,
        OrderStatusService $statusService,
    ): RedirectResponse {
        $this->authorize('updateStatus', $order);

        $status = OrderStatus::from($request->validated('status'));

        $statusService->update(
            $order,
            $status,
            $request->validated('note'),
            $request->user(),
        );

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('status', 'تم تحديث حالة الطلب إلى «'.$status->label().'».');
    }

    public function updatePaymentStatus(
        UpdatePaymentStatusRequest $request,
        Order $order,
    ): RedirectResponse {
        $this->authorize('updatePaymentStatus', $order);

        $paymentStatus = PaymentStatus::from($request->validated('payment_status'));

        if ($order->payment_status === $paymentStatus) {
            return redirect()
                ->route('admin.orders.show', $order)
                ->with('status', 'حالة الدفع محدّثة بالفعل؛ لم يُسجّل تغيير.');
        }

        $order->update(['payment_status' => $paymentStatus]);

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('status', 'تم تحديث حالة الدفع إلى «'.$paymentStatus->label().'». حالة الطلب لم تتغير.');
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
