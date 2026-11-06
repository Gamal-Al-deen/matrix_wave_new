<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\Cart\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login form.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Authenticate the customer and regenerate the session.
     */
    public function store(LoginRequest $request, CartService $cart): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $cart->mergeSessionIntoDatabase($request->user());

        return redirect()->intended(route('home'));
    }

    /**
     * End the authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('home')
            ->with('status', 'تم تسجيل الخروج بنجاح.');
    }
}
