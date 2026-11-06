<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\WalletIconRequest;
use App\Models\Wallet;
use App\Services\Storage\ImageStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Throwable;

class WalletIconController extends Controller
{
    public function store(
        WalletIconRequest $request,
        Wallet $wallet,
        ImageStorageService $storage,
    ): RedirectResponse {
        $this->authorize('update', $wallet);

        $file = $request->file('icon');

        if (! $file instanceof UploadedFile) {
            return back()->with('error', 'تعذر قراءة ملف الأيقونة. اختر صورة صالحة وحاول مرة أخرى.');
        }

        $previousPath = $wallet->icon_storage_path;
        $stored = $storage->store($file, $storage->folder('wallet', $wallet->id));

        try {
            $wallet->update([
                'icon_storage_path' => $stored->path,
                'icon_public_url' => $stored->publicUrl,
            ]);
        } catch (Throwable $exception) {
            $storage->delete($stored->path);

            throw $exception;
        }

        if ($previousPath !== null && $previousPath !== $stored->path) {
            $storage->delete($previousPath);
        }

        return back()->with(
            'status',
            $previousPath === null ? 'تم رفع أيقونة المحفظة.' : 'تم استبدال أيقونة المحفظة.',
        );
    }

    public function destroy(Wallet $wallet, ImageStorageService $storage): RedirectResponse
    {
        $this->authorize('update', $wallet);

        $iconPath = $wallet->icon_storage_path;

        $wallet->update([
            'icon_storage_path' => null,
            'icon_public_url' => null,
        ]);

        if (! $storage->delete($iconPath)) {
            return back()->with(
                'status',
                'تم حذف الأيقونة من المحفظة، لكن تعذر حذف الملف من التخزين.',
            );
        }

        return back()->with('status', 'تم حذف أيقونة المحفظة.');
    }
}
