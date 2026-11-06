<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SocialPlatform;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SocialLinkRequest;
use App\Models\SocialLink;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SocialLinkController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', SocialLink::class);

        $socialLinks = SocialLink::query()
            ->orderBy('sort_order')
            ->orderBy('created_at')
            ->paginate(15);

        return view('admin.social-links.index', compact('socialLinks'));
    }

    public function create(): View
    {
        $this->authorize('create', SocialLink::class);

        return view('admin.social-links.create', [
            'platforms' => SocialPlatform::cases(),
        ]);
    }

    public function store(SocialLinkRequest $request): RedirectResponse
    {
        $this->authorize('create', SocialLink::class);

        $data = $request->validated();
        $data['icon_key'] = $data['platform'];

        $socialLink = SocialLink::query()->create($data);

        return redirect()
            ->route('admin.social-links.edit', $socialLink)
            ->with('status', 'تم إضافة الرابط الاجتماعي.');
    }

    public function edit(SocialLink $socialLink): View
    {
        $this->authorize('update', $socialLink);

        return view('admin.social-links.edit', [
            'socialLink' => $socialLink,
            'platforms' => SocialPlatform::cases(),
        ]);
    }

    public function update(SocialLinkRequest $request, SocialLink $socialLink): RedirectResponse
    {
        $this->authorize('update', $socialLink);

        $data = $request->validated();
        $data['icon_key'] = $data['platform'];

        $socialLink->update($data);

        return redirect()
            ->route('admin.social-links.edit', $socialLink)
            ->with('status', 'تم تحديث الرابط الاجتماعي.');
    }

    public function toggleStatus(SocialLink $socialLink): RedirectResponse
    {
        $this->authorize('update', $socialLink);

        $socialLink->update(['is_active' => ! $socialLink->is_active]);

        return back()->with(
            'status',
            $socialLink->is_active ? 'تم تفعيل الرابط الاجتماعي.' : 'تم تعطيل الرابط الاجتماعي.',
        );
    }

    public function destroy(SocialLink $socialLink): RedirectResponse
    {
        $this->authorize('delete', $socialLink);

        $socialLink->delete();

        return redirect()
            ->route('admin.social-links.index')
            ->with('status', 'تم حذف الرابط الاجتماعي.');
    }
}
