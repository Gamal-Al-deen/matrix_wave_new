<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AboutPageContentRequest;
use App\Models\AboutPageContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AboutPageContentController extends Controller
{
    public function edit(): View
    {
        $this->authorize('view', AboutPageContent::class);

        $about = AboutPageContent::currentOrCreate();
        $about->load('updatedBy');

        return view('admin.about.edit', compact('about'));
    }

    public function update(AboutPageContentRequest $request): RedirectResponse
    {
        $this->authorize('update', AboutPageContent::class);

        $about = AboutPageContent::currentOrCreate();

        $about->update([
            ...$request->validated(),
            'updated_by' => $request->user()?->id,
        ]);

        return redirect()
            ->route('admin.about.edit')
            ->with('status', 'تم حفظ محتوى صفحة «من نحن».');
    }
}
