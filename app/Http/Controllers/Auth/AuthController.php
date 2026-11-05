<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('login');
    }

    public function login(Request $request)
    {
        $creds = $request->validate(['email' => 'required|email', 'password' => 'required']);

        $email = Str::lower($creds['email']);
        $ip    = $request->ip();
        $key   = $email . '|' . $ip;

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            Log::warning('MyLDAP.auth locked out', ['email' => $email, 'ip' => $ip, 'retry_in' => $seconds]);
            ActivityLog::record('auth.locked_out', $email, ['ip' => $ip]);

            return back()->withErrors(['email' => 'Too many attempts. Try again in ' . $seconds . ' seconds.']);
        }

        if (! Auth::attempt($creds, $request->boolean('remember'))) {
            RateLimiter::hit($key, 60);

            // Distinguish "unknown account" from "wrong password" in the log only.
            // The user always sees the same generic message.
            $reason = User::where('email', $email)->exists() ? 'bad_password' : 'unknown_email';

            Log::warning('MyLDAP.auth login failed', [
                'email'    => $email,
                'ip'       => $ip,
                'reason'   => $reason,
                'attempts' => RateLimiter::attempts($key),
            ]);
            ActivityLog::record('auth.login_failed', $email, ['ip' => $ip, 'reason' => $reason]);

            return back()->withInput($request->only('email'))->withErrors(['email' => 'Invalid e-mail or password.']);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();

        $user = $request->user();

        Log::info('MyLDAP.auth login', ['id' => $user->id, 'email' => $user->email, 'ip' => $ip]);
        ActivityLog::record('auth.login', $user->email, array_filter([
            'ip'       => $ip,
            'remember' => $request->boolean('remember') ? 'yes' : null,
        ]));

        return redirect()->intended('/');
    }

    public function logout(Request $request)
    {
        // Capture before Auth::logout() clears the user
        $user = $request->user();

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($user) {
            Log::info('MyLDAP.auth logout', ['id' => $user->id, 'email' => $user->email, 'ip' => $request->ip()]);
            ActivityLog::record('auth.logout', $user->email, ['ip' => $request->ip()]);
        }

        return redirect()->route('login');
    }
}