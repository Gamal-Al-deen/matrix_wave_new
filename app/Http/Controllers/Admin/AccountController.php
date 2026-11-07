<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateAdminAccountRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function edit(Request $request): View
    {
        return view('admin.account.edit', [
            'user' => $request->user(),
        ]);
    }

    public function update(UpdateAdminAccountRequest $request): RedirectResponse
    {
        $user = $request->user();

        $attributes = ['email' => $request->validated('email')];

        if ($request->filled('password')) {
            // The User model casts "password" to hashed, so the plaintext is
            // never stored and the stored value is never read back into a form.
            $attributes['password'] = $request->validated('password');
        }

        $user->update($attributes);

        return redirect()
            ->route('admin.account.edit')
            ->with('status', 'تم تحديث بيانات حساب المدير بنجاح.');
    }
}
