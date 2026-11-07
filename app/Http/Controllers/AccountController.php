<?php

namespace App\Http\Controllers;

use App\Http\Requests\Account\UpdateAccountRequest;
use App\Rules\YemeniPhone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountController extends Controller
{
    /**
     * Show the authenticated customer's own profile details.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();

        return view('account.edit', [
            'user' => $user,
            'phoneNeedsUpdate' => filled($user->phone)
                && ! YemeniPhone::passes(YemeniPhone::normalize($user->phone)),
            'ordersCount' => $user->orders()->count(),
        ]);
    }

    /**
     * Update the editable profile fields without touching credentials or role.
     */
    public function update(UpdateAccountRequest $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->safe()->only(['name', 'phone', 'default_address']);

        if (array_key_exists('phone', $data)) {
            $data['phone'] = $data['phone'] ?: null;
        }

        $user->update($data);

        return redirect()
            ->route('account')
            ->with('status', 'تم حفظ بيانات حسابك بنجاح.');
    }
}
