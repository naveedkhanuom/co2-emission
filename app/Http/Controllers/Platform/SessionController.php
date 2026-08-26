<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Sign-in for the back-office, on the `platform` guard.
 *
 * Separate from the tenant login entirely: a session here is not a session on
 * any client's workspace, and vice versa.
 */
class SessionController extends Controller
{
    public function create()
    {
        if (Auth::guard('platform')->check()) {
            return redirect()->route('platform.tenants.index');
        }

        return view('platform.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // These accounts reach every client on the platform, so brute force is
        // worth more here than against any single tenant login.
        $key = 'platform-login:'.Str::lower($credentials['email']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Too many attempts. Try again in '
                    .RateLimiter::availableIn($key).' seconds.',
            ]);
        }

        if (! Auth::guard('platform')->attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($key, 300);

            throw ValidationException::withMessages([
                'email' => 'Those credentials do not match our records.',
            ]);
        }

        $user = Auth::guard('platform')->user();

        // Deactivating an account has to end access, not just stop new
        // sign-ins — so this is checked after the credentials pass rather
        // than folded into the lookup.
        if (! $user->is_active) {
            Auth::guard('platform')->logout();

            throw ValidationException::withMessages([
                'email' => 'This account is no longer active.',
            ]);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(route('platform.tenants.index'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('platform')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('platform.login');
    }
}
