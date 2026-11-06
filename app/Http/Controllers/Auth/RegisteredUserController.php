<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Services\Cart\CartService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration form.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Create a new customer account and start an authenticated session.
     */
    public function store(RegisterRequest $request, CartService $cart): RedirectResponse
    {
        $user = User::query()->create($request->safe()->only([
            'name',
            'email',
            'phone',
            'password',
        ]));

        event(new Registered($user));

        Auth::login($user);

        $request->session()->regenerate();

        $cart->mergeSessionIntoDatabase($user);

        return redirect()
            ->route('home')
            ->with('status', 'تم إنشاء حسابك وتسجيل دخولك بنجاح.');
    }
}
