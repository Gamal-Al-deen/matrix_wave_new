<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    /**
     * List registered customer accounts for review.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $search = mb_substr(trim((string) $request->query('q', '')), 0, 100);

        $customers = User::query()
            ->where('role', UserRole::Customer->value)
            ->when($search !== '', function ($query) use ($search): void {
                $like = '%'.$this->escapeLike($search).'%';

                $query->where(function ($query) use ($like): void {
                    $query
                        ->where('name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('phone', 'like', $like);
                });
            })
            ->withCount('orders')
            ->latest('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.customers.index', [
            'customers' => $customers,
            'search' => $search,
        ]);
    }

    /**
     * Show a customer's saved profile and own order history without editing it.
     */
    public function show(User $customer): View
    {
        $this->authorize('view', $customer);

        abort_unless($customer->role === UserRole::Customer, 404);

        $customer->loadCount('orders');

        $orders = $customer->orders()
            ->latest('created_at')
            ->paginate(10)
            ->withQueryString();

        return view('admin.customers.show', [
            'customer' => $customer,
            'orders' => $orders,
        ]);
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
