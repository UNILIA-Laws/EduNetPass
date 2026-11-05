<?php

namespace App\Listeners;

use App\Models\ActivityLog;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;

/**
 * Records staff sign-ins, sign-outs and failed attempts.
 * Laravel 11+ discovers these handle* methods automatically.
 * On Laravel 10 or older, register the three events in EventServiceProvider::$listen.
 */
class LogAuthEvents
{
    public function handleLogin(Login $event): void
    {
        ActivityLog::record('auth.login', $event->user->email, [], $event->user->email);
    }

    public function handleLogout(Logout $event): void
    {
        if ($event->user) {
            ActivityLog::record('auth.logout', $event->user->email, [], $event->user->email);
        }
    }

    public function handleFailed(Failed $event): void
    {
        $email = (string) ($event->credentials['email'] ?? '');

        ActivityLog::record('auth.failed', $email ?: null, [], $email ?: null);
    }
}
