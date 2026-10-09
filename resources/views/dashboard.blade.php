@extends('layouts.app')
@section('title', 'eduroam | Dashboard')

@push('styles')
<style>
    .dash { --blue: var(--eduroam-blue); --ink: #172033; --muted: #667085; --line: #E3E8F0; }
    .dash-hero {
        position: relative; overflow: hidden; border-radius: 20px; color: #fff; padding: 2rem 2rem 2.25rem;
        background: linear-gradient(135deg, #003366 0%, #0A4C94 60%, #1F6FC7 100%);
        box-shadow: 0 12px 30px -14px rgba(0,51,102,.6);
    }
    .dash-hero::before, .dash-hero::after {
        content: ""; position: absolute; border-radius: 50%; background: rgba(255,255,255,.07);
    }
    .dash-hero::before { width: 260px; height: 260px; right: -60px; top: -90px; }
    .dash-hero::after  { width: 160px; height: 160px; right: 120px; bottom: -80px; }
    .dash-hero h1 { font-size: 1.7rem; font-weight: 700; letter-spacing: -.02em; margin: 0 0 .3rem; }
    .dash-hero p  { margin: 0; color: rgba(255,255,255,.8); }
    .dash-hero .btn-refresh {
        position: relative; z-index: 1; background: rgba(255,255,255,.15); color: #fff; border: 1px solid rgba(255,255,255,.25);
        border-radius: 999px; font-size: .82rem; font-weight: 600; padding: .4rem .9rem; text-decoration: none; transition: background .15s;
    }
    .dash-hero .btn-refresh:hover { background: rgba(255,255,255,.25); color: #fff; }

    /* Stat tiles */
    .stat {
        background: #fff; border: 1px solid var(--line); border-radius: 16px; padding: 1.15rem 1.25rem; height: 100%;
        display: flex; align-items: center; gap: 1rem; box-shadow: 0 1px 2px rgba(23,32,51,.04);
    }
    .stat .ico {
        flex: none; width: 52px; height: 52px; border-radius: 14px; display: grid; place-items: center; font-size: 1.3rem;
    }
    .stat .ico.blue   { background: #E8EEFC; color: #2557D6; }
    .stat .ico.green  { background: #E3F5EC; color: #178A5C; }
    .stat .ico.orange { background: #FFF1DC; color: #D97706; }
    .stat .ico.purple { background: #EFE9FC; color: #6D42D3; }
    .stat .num   { font-size: 1.85rem; font-weight: 700; line-height: 1; color: var(--ink); letter-spacing: -.02em; }
    .stat .label { font-size: .82rem; color: var(--muted); margin-top: .3rem; }

    /* Panels */
    .dpanel { background: #fff; border: 1px solid var(--line); border-radius: 16px; padding: 1.25rem 1.35rem; height: 100%; box-shadow: 0 1px 2px rgba(23,32,51,.04); }
    .dpanel h2 { font-size: 1rem; font-weight: 700; color: var(--ink); margin: 0 0 1rem; }
    .dpanel h2 i { color: var(--blue); margin-right: .4rem; }

    .campus-row + .campus-row { margin-top: 1rem; }
    .campus-row .top { display: flex; justify-content: space-between; font-size: .88rem; margin-bottom: .35rem; }
    .campus-row .top b { color: var(--ink); }
    .campus-row .top span { color: var(--muted); }
    .bar { height: 8px; background: #EEF1F5; border-radius: 999px; overflow: hidden; }
    .bar > div { height: 100%; border-radius: 999px; background: linear-gradient(90deg, #0A4C94, #3B8BEB); }

    /* Action cards */
    .action {
        display: flex; align-items: center; gap: 1rem; background: #fff; border: 1px solid var(--line); border-radius: 16px;
        padding: 1.1rem 1.25rem; text-decoration: none; color: inherit; height: 100%;
        box-shadow: 0 1px 2px rgba(23,32,51,.04); transition: transform .15s, box-shadow .15s, border-color .15s;
    }
    .action:hover { transform: translateY(-3px); border-color: #BFD0F2; box-shadow: 0 14px 28px -16px rgba(0,51,102,.45); color: inherit; }
    .action .ico { flex: none; width: 46px; height: 46px; border-radius: 12px; display: grid; place-items: center; font-size: 1.15rem; color: #fff; background: var(--blue); }
    .action h3 { font-size: .98rem; font-weight: 650; margin: 0 0 .15rem; color: var(--ink); }
    .action p  { font-size: .8rem; color: var(--muted); margin: 0; line-height: 1.4; }
    .action .go { margin-left: auto; color: #A5AEBD; transition: transform .15s, color .15s; }
    .action:hover .go { transform: translateX(4px); color: var(--blue); }

    .dash-alert { display: flex; gap: .6rem; background: #FCE9E9; color: #8E2323; border-radius: 12px; padding: .75rem 1rem; font-size: .88rem; }
</style>
@endpush

@section('content')
@php
    $hour = (int) now()->format('G');
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
    $total = $stats['total'] ?? 0;
@endphp

<div class="dash container py-4 py-lg-5">

    {{-- Hero --}}
    <div class="dash-hero mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div style="position:relative;z-index:1">
                <h1>{{ $greeting }}, {{ \Illuminate\Support\Str::of(auth()->user()->name)->before(' ') }}</h1>
                <p>Here is what is happening with eduroam accounts today.</p>
            </div>
            <a href="{{ route('dashboard', ['refresh' => 1]) }}" class="btn-refresh">
                <i class="fa-solid fa-rotate me-1"></i> Refresh
                @if($stats) <span class="opacity-75">· updated {{ $stats['generated_at'] }}</span> @endif
            </a>
        </div>
    </div>

    @if($error)
        <div class="dash-alert mb-4">
            <i class="fa-solid fa-triangle-exclamation mt-1"></i>
            <span>Could not load statistics from LDAP: {{ $error }}</span>
        </div>
    @endif

    {{-- Stat tiles --}}
    <div class="row g-3 g-lg-4 mb-4">
        <div class="col-6 col-xl-3">
            <div class="stat">
                <div class="ico blue"><i class="fa-solid fa-users"></i></div>
                <div>
                    <div class="num">{{ $stats ? number_format($total) : '—' }}{{ !empty($stats['capped']) ? '+' : '' }}</div>
                    <div class="label">Total students</div>
                </div>
            </div>
        </div>

        @foreach(($stats['campuses'] ?? []) as $i => $c)
            @php $tones = ['green', 'orange', 'purple']; @endphp
            <div class="col-6 col-xl-3">
                <div class="stat">
                    <div class="ico {{ $tones[$i % 3] }}"><i class="fa-solid fa-building-columns"></i></div>
                    <div>
                        <div class="num">{{ number_format($c['count']) }}</div>
                        <div class="label">{{ $c['label'] }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-3 g-lg-4">

        {{-- Campus distribution --}}
        <div class="col-lg-5">
            <div class="dpanel">
                <h2><i class="fa-solid fa-chart-simple"></i>Students by campus</h2>

                @forelse(($stats['campuses'] ?? []) as $c)
                    @php $pct = $total > 0 ? round($c['count'] / $total * 100) : 0; @endphp
                    <div class="campus-row">
                        <div class="top"><b>{{ $c['label'] }}</b><span>{{ number_format($c['count']) }} · {{ $pct }}%</span></div>
                        <div class="bar"><div style="width: {{ $pct }}%"></div></div>
                    </div>
                @empty
                    <p class="text-muted small mb-0">No data available yet.</p>
                @endforelse

                @if(!empty($stats['capped']))
                    <p class="text-muted small mt-3 mb-0">
                        <i class="fa-solid fa-circle-info me-1"></i>
                        The count is capped at {{ number_format(config('ldap.max_list')) }}, so the real total may be higher.
                    </p>
                @endif
            </div>
        </div>

        {{-- Quick actions --}}
        <div class="col-lg-7">
            <div class="dpanel">
                <h2><i class="fa-solid fa-bolt"></i>Quick actions</h2>

                <div class="row g-3">
                    <div class="col-md-6">
                        <a href="{{ route('enroll') }}" class="action">
                            <span class="ico"><i class="fa-solid fa-user-plus"></i></span>
                            <div>
                                <h3>Add students</h3>
                                <p>One by one or from a CSV/Excel file.</p>
                            </div>
                            <i class="fa-solid fa-arrow-right go"></i>
                        </a>
                    </div>

                    <div class="col-md-6">
                        <a href="{{ route('users.index') }}" class="action">
                            <span class="ico" style="background:#178A5C"><i class="fa-solid fa-users-gear"></i></span>
                            <div>
                                <h3>Manage users</h3>
                                <p>Search, edit, reset passwords, delete.</p>
                            </div>
                            <i class="fa-solid fa-arrow-right go"></i>
                        </a>
                    </div>

                    <div class="col-md-6">
                        <a href="{{ route('download.sample') }}" class="action">
                            <span class="ico" style="background:#D97706"><i class="fa-solid fa-file-arrow-down"></i></span>
                            <div>
                                <h3>Sample file</h3>
                                <p>Download the bulk upload template.</p>
                            </div>
                            <i class="fa-solid fa-arrow-right go"></i>
                        </a>
                    </div>

                    @if(auth()->user()->is_admin && Route::has('settings.edit'))
                        <div class="col-md-6">
                            <a href="{{ route('settings.edit') }}" class="action">
                                <span class="ico" style="background:#6D42D3"><i class="fa-solid fa-gear"></i></span>
                                <div>
                                    <h3>Settings</h3>
                                    <p>Campuses, entitlements and limits.</p>
                                </div>
                                <i class="fa-solid fa-arrow-right go"></i>
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>

    </div>
</div>
@endsection