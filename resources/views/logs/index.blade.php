@extends('layouts.app')
@section('title', 'MyLDAP | Activity log')

@section('content')
@include('partials.usr-style')

<style>
    .usr .chip.fail { background: var(--bad-soft); color: var(--bad); }
    .usr .toolbar .form-control[type=date] { flex: 0 1 160px; }
    .usr .tbl td.when { white-space: nowrap; }
    .usr .tbl td.when .d { font-weight: 600; }
    .usr .tbl td.when .t { color: var(--muted); font-size: .78rem; }
    .usr .tbl td.ip { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: .78rem; color: var(--muted); white-space: nowrap; }
    .usr .tbl td.who { font-weight: 600; max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .usr .kv { color: var(--muted); font-size: .78rem; }
    .usr .kv b { color: var(--ink); font-weight: 600; }
</style>

<div class="usr">
<div class="container">

    @include('users._alerts')

    <div class="usr-head">
        <div>
            <h1 class="usr-title">Activity log</h1>
            <p class="usr-sub">Admin actions and staff sign-ins, newest first. Entries are kept for 12 months.</p>
        </div>
        <span class="count">{{ number_format($logs->total()) }} entr{{ $logs->total() === 1 ? 'y' : 'ies' }}</span>
    </div>

    <div class="panel">
        <form method="GET" action="{{ route('logs.index') }}" class="toolbar">
            <div class="search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="search" name="q" value="{{ $q }}" class="form-control" placeholder="Search e-mail, subject or IP">
            </div>
            <select name="type" class="form-select" aria-label="Type">
                <option value="">All activity</option>
                <option value="audit" @selected($type === 'audit')>Admin actions</option>
                <option value="auth"  @selected($type === 'auth')>Sign-ins</option>
            </select>
            <input type="date" name="from" value="{{ $from }}" class="form-control" aria-label="From date">
            <input type="date" name="to"   value="{{ $to }}"   class="form-control" aria-label="To date">
            <button class="btn-primary-soft"><i class="fa-solid fa-filter me-2"></i>Filter</button>
            @if($q !== '' || $type !== '' || $from || $to)
                <a href="{{ route('logs.index') }}" class="btn-clear">Clear</a>
            @endif
        </form>

        <div class="table-responsive">
            <table class="tbl">
                <thead>
                    <tr><th>When</th><th>Who</th><th>Event</th><th>Subject</th><th>Details</th><th>IP</th></tr>
                </thead>
                <tbody>
                @forelse($logs as $l)
                    <tr>
                        <td class="when" title="{{ $l->created_at->toDateTimeString() }}">
                            <div class="d">{{ $l->created_at->format('d M Y') }}</div>
                            <div class="t">{{ $l->created_at->format('H:i:s') }} · {{ $l->created_at->diffForHumans() }}</div>
                        </td>
                        <td class="who">{{ $l->actor_email ?: 'Unknown' }}</td>
                        <td><span class="chip {{ $l->chip }}">{{ $l->label }}</span></td>
                        <td class="desc">{{ $l->subject ?: '-' }}</td>
                        <td>
                            @forelse(($l->details ?? []) as $k => $v)
                                <div class="kv"><b>{{ $k }}</b>: {{ is_array($v) ? implode(', ', $v) : $v }}</div>
                            @empty
                                <span class="desc">-</span>
                            @endforelse
                        </td>
                        <td class="ip" title="{{ $l->user_agent }}">{{ $l->ip ?: '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="empty"><i class="fa-solid fa-clock-rotate-left"></i>No activity matches these filters.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="pager">{{ $logs->links() }}</div>
    </div>

</div>
</div>
@endsection