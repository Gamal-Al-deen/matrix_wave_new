<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\WalletRequest;
use App\Models\Wallet;
use App\Services\Storage\ImageStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class WalletController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Wallet::class);

        $wallets = Wallet::query()
            ->withCount('orders')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(15);

        return view('admin.wallets.index', compact('wallets'));
    }

    public function create(): View
    {
        $this->authorize('create', Wallet::class);

        return view('admin.wallets.create');
    }

    public function store(WalletRequest $request): RedirectResponse
    {
        $this->authorize('create', Wallet::class);

        $wallet = Wallet::query()->create($request->validated());

        return redirect()
            ->route('admin.wallets.edit', $wallet)
            ->with('status', 'تم إنشاء المحفظة بنجاح. يمكنك الآن رفع أيقونتها.');
    }

    public function edit(Wallet $wallet): View
    {
        $this->authorize('update', $wallet);

        return view('admin.wallets.edit', compact('wallet'));
    }

    public function update(WalletRequest $request, Wallet $wallet): RedirectResponse
    {
        $this->authorize('update', $wallet);

        $wallet->update($request->validated());

        return redirect()
            ->route('admin.wallets.edit', $wallet)
            ->with('status', 'تم تحديث المحفظة بنجاح.');
    }

    public function toggleStatus(Wallet $wallet): RedirectResponse
    {
        $this->authorize('update', $wallet);

        $wallet->update(['is_active' => ! $wallet->is_active]);

        return back()->with(
            'status',
            $wallet->is_active ? 'تم تفعيل المحفظة.' : 'تم تعطيل المحفظة.',
        );
    }

    public function destroy(Wallet $wallet, ImageStorageService $storage): RedirectResponse
    {
        $this->authorize('delete', $wallet);

        if ($wallet->orders()->exists()) {
            return back()->with(
                'error',
                'لا يمكن حذف محفظة مرتبطة بطلبات. عطّلها بدلًا من ذلك للحفاظ على بيانات الطلبات.',
            );
        }

        $iconPath = $wallet->icon_storage_path;
        $wallet->delete();
        $iconDeleted = $storage->delete($iconPath);

        return redirect()
            ->route('admin.wallets.index')
            ->with(
                'status',
                $iconDeleted
                    ? 'تم حذف المحفظة.'
                    : 'تم حذف المحفظة، لكن تعذر حذف ملف الأيقونة من التخزين.',
            );
    }
}
