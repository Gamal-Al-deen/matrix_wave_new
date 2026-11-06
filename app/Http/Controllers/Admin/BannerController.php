<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BannerImageRequest;
use App\Http\Requests\Admin\BannerRequest;
use App\Http\Requests\Admin\BannerStoreRequest;
use App\Models\Banner;
use App\Services\Storage\ImageStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class BannerController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Banner::class);

        $banners = Banner::query()
            ->orderBy('sort_order')
            ->orderBy('created_at')
            ->paginate(15);

        return view('admin.banners.index', compact('banners'));
    }

    public function create(): View
    {
        $this->authorize('create', Banner::class);

        return view('admin.banners.create');
    }

    public function store(
        BannerStoreRequest $request,
        ImageStorageService $storage,
    ): RedirectResponse {
        $this->authorize('create', Banner::class);

        $file = $request->file('image');

        if (! $file instanceof UploadedFile) {
            return back()->with('error', 'تعذر قراءة ملف صورة البانر. اختر صورة صالحة وحاول مرة أخرى.');
        }

        $banner = new Banner;
        $banner->id = (string) Str::uuid();
        $stored = $storage->store($file, $storage->folder('banner', $banner->id));

        try {
            $banner->fill($request->safe()->except('image'));
            $banner->image_storage_path = $stored->path;
            $banner->image_public_url = $stored->publicUrl;
            $banner->save();
        } catch (Throwable $exception) {
            $storage->delete($stored->path);

            throw $exception;
        }

        return redirect()
            ->route('admin.banners.edit', $banner)
            ->with('status', 'تم إنشاء البانر ورفع صورته بنجاح.');
    }

    public function edit(Banner $banner): View
    {
        $this->authorize('update', $banner);

        return view('admin.banners.edit', compact('banner'));
    }

    public function update(BannerRequest $request, Banner $banner): RedirectResponse
    {
        $this->authorize('update', $banner);

        $banner->update($request->validated());

        return redirect()
            ->route('admin.banners.edit', $banner)
            ->with('status', 'تم تحديث بيانات البانر.');
    }

    public function toggleStatus(Banner $banner): RedirectResponse
    {
        $this->authorize('update', $banner);

        $banner->update(['is_active' => ! $banner->is_active]);

        return back()->with(
            'status',
            $banner->is_active
                ? 'تم تفعيل البانر. لن يظهر للزوار خارج فترة النشر المحددة إن وجدت.'
                : 'تم تعطيل البانر.',
        );
    }

    public function replaceImage(
        BannerImageRequest $request,
        Banner $banner,
        ImageStorageService $storage,
    ): RedirectResponse {
        $this->authorize('update', $banner);

        $file = $request->file('image');

        if (! $file instanceof UploadedFile) {
            return back()->with('error', 'تعذر قراءة ملف صورة البانر. اختر صورة صالحة وحاول مرة أخرى.');
        }

        $previousPath = $banner->image_storage_path;
        $stored = $storage->store($file, $storage->folder('banner', $banner->id));

        try {
            $banner->update([
                'image_storage_path' => $stored->path,
                'image_public_url' => $stored->publicUrl,
            ]);
        } catch (Throwable $exception) {
            $storage->delete($stored->path);

            throw $exception;
        }

        if ($previousPath !== null && $previousPath !== $stored->path) {
            $storage->delete($previousPath);
        }

        return back()->with('status', 'تم استبدال صورة البانر.');
    }

    public function destroy(Banner $banner, ImageStorageService $storage): RedirectResponse
    {
        $this->authorize('delete', $banner);

        $imagePath = $banner->image_storage_path;
        $banner->delete();
        $imageDeleted = $storage->delete($imagePath);

        return redirect()
            ->route('admin.banners.index')
            ->with(
                'status',
                $imageDeleted
                    ? 'تم حذف البانر وصورته.'
                    : 'تم حذف البانر، لكن تعذر حذف ملف الصورة من التخزين.',
            );
    }
}
