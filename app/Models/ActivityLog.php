<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

class ActivityLog extends Model
{
    use Prunable;

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'details'    => 'array',
        'created_at' => 'datetime',
    ];

    /** event => [label, chip class] */
    public const EVENTS = [
        'auth.login'      => ['Signed in',        'type-staff'],
        'auth.logout'     => ['Signed out',       ''],
        'auth.failed'     => ['Failed sign-in',   'fail'],
        'staff.created'   => ['Staff added',      'type-student'],
        'staff.updated'   => ['Staff updated',    'type-student'],
        'staff.deleted'   => ['Staff deleted',    'fail'],
        'campus.created'  => ['Campus added',     'campus'],
        'campus.updated'  => ['Campus updated',   'campus'],
        'campus.deleted'  => ['Campus deleted',   'fail'],
        'ldapuser.created' => ['User added',      'type-guest'],
        'ldapuser.updated' => ['User updated',    'type-guest'],
        'ldapuser.deleted' => ['User deleted',    'fail'],
        'ldapuser.password_reset' => ['Password reset', 'type-guest'],
    ];

    /**
     * Write one log entry. Never throws: logging must not break the action being logged.
     *
     * @param  string       $event        key from EVENTS (or any "area.action" string)
     * @param  string|null  $subject      human label of what was affected, e.g. an e-mail or campus name
     * @param  array        $details      small extra facts; never put passwords or secrets here
     * @param  string|null  $actorEmail   override when nobody is signed in (failed sign-in)
     */
    public static function record(string $event, ?string $subject = null, array $details = [], ?string $actorEmail = null): void
    {
        try {
            $user = auth()->user();

            static::create([
                'actor_id'    => $user?->id,
                'actor_email' => $actorEmail ?? $user?->email,
                'event'       => $event,
                'subject'     => $subject,
                'details'     => $details ?: null,
                'ip'          => request()?->ip(),
                'user_agent'  => mb_substr((string) request()?->userAgent(), 0, 255),
                'created_at'  => now(),
            ]);
        } catch (\Throwable $e) {
            \Log::warning('ActivityLog write failed', ['event' => $event, 'error' => $e->getMessage()]);
        }
    }

    public function getLabelAttribute(): string
    {
        return self::EVENTS[$this->event][0] ?? $this->event;
    }

    public function getChipAttribute(): string
    {
        return self::EVENTS[$this->event][1] ?? '';
    }

    public function isAuth(): bool
    {
        return str_starts_with($this->event, 'auth.');
    }

    /** Automatically delete entries older than 12 months with: php artisan model:prune */
    public function prunable()
    {
        return static::where('created_at', '<', now()->subMonths(12));
    }
}
