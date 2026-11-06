<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SettingsLogoRequest;
use App\Models\SiteSetting;
use App\Services\Storage\ImageStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Throwable;

class SettingsLogoController extends Controller
{
    public function store(
        SettingsLogoRequest $request,
        ImageStorageService $storage,
    ): RedirectResponse {
        $this->authorize('update', SiteSetting::class);

        $file = $request->file('logo');

        if (! $file instanceof UploadedFile) {
            return back()->with('error', 'تعذر قراءة ملف الشعار. اختر صورة صالحة وحاول مرة أخرى.');
        }

        $settings = SiteSetting::currentOrCreate();
        $previousPath = $settings->logo_storage_path;
        $stored = $storage->store($file, $storage->folder('site'));

        try {
            $settings->update([
                'logo_storage_path' => $stored->path,
                'logo_public_url' => $stored->publicUrl,
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
            $previousPath === null ? 'تم رفع شعار الموقع.' : 'تم استبدال شعار الموقع.',
        );
    }

    public function destroy(ImageStorageService $storage): RedirectResponse
    {
        $this->authorize('update', SiteSetting::class);

        $settings = SiteSetting::currentOrCreate();
        $previousPath = $settings->logo_storage_path;

        $settings->update([
            'logo_storage_path' => null,
            'logo_public_url' => null,
        ]);

        if ($previousPath === null) {
            return back()->with('status', 'تمت إعادة الشعار الافتراضي.');
        }

        if (! $storage->delete($previousPath)) {
            return back()->with(
                'status',
                'تمت إعادة الشعار الافتراضي، لكن تعذر حذف ملف الشعار السابق من التخزين.',
            );
        }

        return back()->with('status', 'تمت إعادة الشعار الافتراضي وحذف الملف المرفوع.');
    }
}
