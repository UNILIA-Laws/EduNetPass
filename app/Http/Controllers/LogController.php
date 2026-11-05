<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class LogController extends Controller
{
    public function index(Request $request)
    {
        $q    = trim((string) $request->query('q', ''));
        $type = in_array($request->query('type'), ['audit', 'auth'], true) ? $request->query('type') : '';
        $from = $request->query('from');
        $to   = $request->query('to');

        $logs = ActivityLog::query()
            ->when($type === 'auth',  fn ($w) => $w->where('event', 'like', 'auth.%'))
            ->when($type === 'audit', fn ($w) => $w->where('event', 'not like', 'auth.%'))
            ->when($q !== '', fn ($w) => $w->where(
                fn ($x) => $x->where('actor_email', 'like', "%{$q}%")
                    ->orWhere('subject', 'like', "%{$q}%")
                    ->orWhere('ip', 'like', "%{$q}%")
            ))
            ->when($from, fn ($w) => $w->where('created_at', '>=', $from . ' 00:00:00'))
            ->when($to,   fn ($w) => $w->where('created_at', '<=', $to . ' 23:59:59'))
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('logs.index', [
            'logs' => $logs,
            'q'    => $q,
            'type' => $type,
            'from' => $from,
            'to'   => $to,
        ]);
    }
}
