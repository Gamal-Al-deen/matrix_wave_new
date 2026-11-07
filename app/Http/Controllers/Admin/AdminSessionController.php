<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\AdminLoginRequest;
use App\Services\Orders\AdminOrderIndicator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminSessionController extends Controller
{
    /**
     * Display the dedicated administrator login form.
     */
    public function create(Request $request): View|RedirectResponse
    {
        if ($request->user()?->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.auth.login');
    }

    /**
     * Authenticate an administrator.
     */
    public function store(AdminLoginRequest $request, AdminOrderIndicator $indicator): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // Establish the baseline for the "new orders" indicator at login time.
        $indicator->markViewed($request);

        return redirect()->intended(route('admin.dashboard'));
    }

    /**
     * End the administrator session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('admin.login')
            ->with('status', 'تم تسجيل الخروج من لوحة الإدارة.');
    }
}
