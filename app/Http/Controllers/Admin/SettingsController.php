<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SettingsRequest;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(): View
    {
        $this->authorize('view', SiteSetting::class);

        $settings = SiteSetting::currentOrCreate();

        return view('admin.settings.edit', compact('settings'));
    }

    public function update(SettingsRequest $request): RedirectResponse
    {
        $this->authorize('update', SiteSetting::class);

        $settings = SiteSetting::currentOrCreate();
        $previousCurrency = $settings->currency_code;
        $settings->fill($request->validated());
        $settings->save();

        $message = 'تم حفظ إعدادات الموقع.';

        if ($previousCurrency !== $settings->currency_code) {
            $message .= ' تغيّرت عملة الموقع فقط؛ لم تُحوّل أسعار المنتجات ولم تتغير الطلبات السابقة. راجع أسعار المنتجات يدويًا.';
        }

        return redirect()
            ->route('admin.settings.edit')
            ->with('status', $message);
    }
}
